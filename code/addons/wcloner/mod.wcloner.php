<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class wcloner {


	public function __construct()
	{

	}

	function clone_entry()
	{

		// Disable error reporting so that the AJAX response doesn't fail

		$debug = false;

		if (! $debug)
		{
			error_reporting(0);
			ini_set('display_errors', 0);
		}

		$entry_id = ee()->input->post('entry_id', false);

		// Creates new entry

		$old_entry = ee('Model')->get('ChannelEntry')
			->filter('entry_id', $entry_id)
			->first();

		if ($old_entry)
		{
			$url_title = 'cloned-' . $old_entry->url_title;
			$title = '[Cloned] ' . $old_entry->title;

			$old_entry->setProperty('entry_id',0);
			$old_entry->setProperty('entry_date', ($old_entry->entry_date + 1));
			$old_entry->setProperty('edit_date', ($old_entry->entry_date + 1));
			$old_entry->setProperty('url_title', $url_title);
			$old_entry->setProperty('title', $title);
			$old_entry->setProperty('status', 'closed');
			$entry = ee('Model')->make('ChannelEntry', $old_entry->getValues());

			$entry = $this->pre_process_assets_fields($entry, $entry_id);

			$entry->save();

			$old_entry->setProperty('entry_id',$entry_id);

			$this->process_grid_fields($entry, $old_entry);
			$this->assign_cats($entry->entry_id, $entry_id);
			$this->assign_rels($entry->entry_id, $entry_id);

			if (substr(ee()->config->item('app_version'), 0,2) === '4.')
			{
				$this->process_fluid_fields($entry, $old_entry);
			}

			$this->process_bloqs_fields($entry, $old_entry);
			$this->process_assets_fields($entry, $old_entry);
			$this->process_tagger_entries($entry->entry_id, $entry_id);
			$this->process_calendar_events($entry->entry_id, $entry_id);
			$this->process_seolite($entry->entry_id, $entry_id);
			$this->process_channel_videos($entry->entry_id, $entry_id);
			$this->process_structure_urls($entry->entry_id, $entry_id);

			// Send json response

			ee()->config->config['send_headers'] = NULL;
			@header('Content-Type: application/json; charset=UTF-8');
			$fields['cloned'] = true;
			$fields['entry'] = $entry->entry_id;
			ee()->output->send_ajax_response(json_encode($fields));
		}
	}

	function get_fields($entry, $field_type)
	{

		$return_fields = array();

		// EE4

		if (substr(ee()->config->item('app_version'), 0,2) === '4.')
		{
			$entry_channel = ee('Model')->get('Channel')
				->filter('channel_id', $entry->channel_id)
				->first();

			$fields = $entry_channel->getAllCustomFields();


			foreach ($fields as $field)
			{
				if ($field->field_type === $field_type)
				{
					$return_fields[] = $field->field_id;
				}
			}
		}

		// EE3

		else
		{

			$query = ee()->db->select('field_group')
				->from('channels')
				->where('channel_id', $entry->channel_id)
				->get();

			$field_group = $query->row()->field_group;

			$query = ee()->db->select('field_id')
				->from('channel_fields')
				->where(array('group_id' => $field_group, 'field_type' => $field_type))
				->get();

			foreach ($query->result() as $row)
			{
				$return_fields[] = $row->field_id;
			}
		}

		return $return_fields;

	}

	// Assigns categories to entry after it's created

	function assign_cats($entry_id, $old_entry)
	{
		$cats = ee()->db->select('*')
			->from('category_posts')
			->where('entry_id', $old_entry)
			->get();

		foreach ($cats->result() as $row)
		{
			ee()->db->insert('category_posts', array('entry_id' => $entry_id, 'cat_id' => $row->cat_id));
		}
	}

	// Assign relationships

	function assign_rels($entry_id, $old_entry)
	{
		$rels = ee()->db->select('*')
			->from('relationships')
			->where('parent_id', $old_entry)
			->get();

		foreach ($rels->result() as $row)
		{
			unset($row->relationship_id);
			$row->parent_id = $entry_id;
			ee()->db->insert('relationships', $row);
		}
	}

	function process_fluid_fields($entry, $old_entry)
	{

		// Get fields

		$fields = $this->get_fields($entry, 'fluid_field');

		foreach ($fields as $row)
		{
			// Go through each field

			$fluid_field = 'channel_data_field_' . $row;

			$fluid_query = ee()->db->select('*')
				->from($fluid_field)
				->where('entry_id', $old_entry->entry_id)
				->get();

			foreach ($fluid_query->result() as $fluid_row)
			{
				// Update field data for search

				$fluid_row->entry_id = $entry->entry_id;
				unset($fluid_row->id);

				ee()->db->update($fluid_field, $fluid_row, array('entry_id' => $entry->entry_id));

				ee()->db->where('entry_id', $entry->entry_id);
				ee()->db->from($fluid_field);
				$fluid_id = ee()->db->get()->row()->id;

				$fluid_f_query = ee()->db->select('*')
					->from('fluid_field_data')
					->where(array('entry_id' => $old_entry->entry_id, 'fluid_field_id' => $row))
					->get();

				// Clone fluid field rows

				foreach ($fluid_f_query->result() as $fluid_f_row)
				{

					$fluid_row_id = $fluid_f_row->id;
					unset($fluid_f_row->id);

					// Also add rows for each field

					$field_table = 'channel_data_field_' . $fluid_f_row->field_id;

					$fluid_fields_query = ee()->db->select('*')
						->from($field_table)
						->where('id', $fluid_f_row->field_data_id)
						->get();

					foreach ($fluid_fields_query->result() as $fluid_field_row_data)
					{
						$fluid_field_row_data->id = $fluid_id;
						ee()->db->insert($field_table, $fluid_field_row_data);
					}


					$fluid_f_row->entry_id = $entry->entry_id;
					$fluid_f_row->field_data_id = $fluid_id;
					ee()->db->insert('fluid_field_data', $fluid_f_row);
					$new_fluid_row_id = ee()->db->insert_id();

					// Update if it's a grid field

					$grid_table = 'channel_grid_field_' . $fluid_f_row->field_id;

					if (ee()->db->table_exists($grid_table))
					{
						ee()->db->update($grid_table, array('fluid_field_data_id' => $new_fluid_row_id), array('entry_id' => $entry->entry_id, 'fluid_field_data_id' => $fluid_row_id));
					}
				}

			}
		}
	}

	function process_grid_fields($entry, $old_entry)
	{

		// Get fields

		$fields = $this->get_fields($entry, 'grid');

		foreach ($fields as $row)
		{
			$grid_field = 'channel_grid_field_' . $row;

			$grid_query = ee()->db->select('*')
				->from($grid_field)
				->where('entry_id', $old_entry->entry_id)
				->get();

			foreach ($grid_query->result() as $grid_row)
			{
				unset($grid_row->row_id);
				$grid_row->entry_id = $entry->entry_id;
				ee()->db->insert($grid_field, $grid_row);
			}
		}
	}

	function process_bloqs_fields($entry, $old_entry)
	{

		// Get field group id

		$fields = $this->get_fields($entry, 'bloqs');

		// Process each bloqs field

		foreach ($fields as $row)
		{
			$bloqs_query = ee()->db->select('*')
				->from('blocks_block')
				->where(array('entry_id'=> $old_entry->entry_id, 'field_id' => $row))
				->get();

			foreach ($bloqs_query->result() as $bloqs_row)
			{
				// Save original bloqs id

				$bloq_id = $bloqs_row->id;

				unset($bloqs_row->id);
				$bloqs_row->entry_id = $entry->entry_id;
				ee()->db->insert('blocks_block', $bloqs_row);
				$new_bloqs_id = ee()->db->insert_id();

				// Go through atoms for each bloq

				$atoms_query = ee()->db->select('*')
					->from('blocks_atom')
					->where('block_id', $bloq_id)
					->get();

				foreach ($atoms_query->result() as $atoms_row)
				{
					unset($atoms_row->id);
					$atoms_row->block_id = $new_bloqs_id;
					ee()->db->insert('blocks_atom', $atoms_row);
					$new_atom_id = ee()->db->insert_id();

					// Check to see if it's a relationship field and update relationships as needed

					$rel_query = ee()->db->select('type')
						->from('blocks_atomdefinition')
						->where('id', $atoms_row->atomdefinition_id)
						->get();

					if ($rel_query->row()->type == 'relationship')
					{

						ee()->db->update('relationships', array('grid_row_id' => $atoms_row->block_id), array('parent_id' => $entry->entry_id, 'grid_field_id' => $row, 'grid_col_id' => $atoms_row->atomdefinition_id, 'grid_row_id' => $bloq_id));

					}

				}

			}
		}
	}

	// Assets fields

	function pre_process_assets_fields($entry, $old_entry)
	{

		if (ee()->db->table_exists('assets_selections'))
		{
			$assets_query = ee()->db->select('*')
				->from('assets_selections')
				->where('entry_id', $old_entry)
				->get();

			foreach ($assets_query->result() as $assets_row)
			{
				$entry->{'field_id_' . $assets_row->field_id} = $assets_row->file_id;
			}
		}

		return $entry;
	}

	function process_assets_fields($entry, $old_entry)
	{

		if (ee()->db->table_exists('assets_selections'))
		{
			$assets_query = ee()->db->select('*')
				->from('assets_selections')
				->where('entry_id', $old_entry->entry_id)
				->get();

			foreach ($assets_query->result() as $assets_row)
			{
				$assets_row->entry_id = $entry->entry_id;
				ee()->db->insert('assets_selections', $assets_row);
			}
		}
	}

	// Solspace Tagger

	function process_tagger_entries($entry_id, $old_entry)
	{
		if (ee()->db->table_exists('tagger_links'))
		{
			$query = ee()->db->select('*')
				->from('tagger_links')
				->where('entry_id', $old_entry)
				->get();

			foreach ($query->result() as $row)
			{
				unset($row->rel_id);
				$row->entry_id = $entry_id;
				ee()->db->insert('tagger_links', $row);

				// Update tag totals

                ee()->db->set('total_entries', '(`total_entries` + 1)', FALSE);
                ee()->db->where('tag_id', $row->tag_id);
                ee()->db->where('site_id', $row->site_id);
                ee()->db->update('exp_tagger');

			}
		}
	}

	// Solspace Calendar

	function process_calendar_events($entry_id, $old_entry)
	{
		if (ee()->db->table_exists('calendar_events'))
		{
			$query = ee()->db->select('*')
				->from('calendar_events')
				->where('entry_id', $old_entry)
				->get();

			foreach ($query->result() as $row)
			{
				unset($row->id);
				$row->entry_id = $entry_id;
				ee()->db->insert('calendar_events', $row);
			}
		}
	}

	// SEO Lite

	function process_seolite($entry_id, $old_entry)
	{
		if (ee()->db->table_exists('seolite_content'))
		{
			$query = ee()->db->select('*')
				->from('seolite_content')
				->where('entry_id', $old_entry)
				->get();

			foreach ($query->result() as $row)
			{
				unset($row->seolite_content_id);
				$row->entry_id = $entry_id;
				ee()->db->update('seolite_content', $row, array('entry_id' => $entry_id));
			}
		}
	}

	// Channel Videos

	function process_channel_videos($entry_id, $old_entry)
	{
		if (ee()->db->table_exists('channel_videos'))
		{
			$query = ee()->db->select('*')
				->from('channel_videos')
				->where('entry_id', $old_entry)
				->get();

			foreach ($query->result() as $row)
			{
				unset($row->video_id);
				$row->entry_id = $entry_id;
				ee()->db->insert('channel_videos', $row);
			}
		}
	}

	function process_structure_urls($entry_id, $old_entry)
	{
		$query = ee()->db->select('site_pages')
			->from('sites')
			->where('site_id', ee()->config->item('site_id'))
			->get();

		$pages = $query->row()->site_pages;
		if ($pages)
		{
			$pages = unserialize(base64_decode($pages));

			if (isset($pages[1]['uris'][$old_entry]))
			{
				$entry_uri = $pages[1]['uris'][$old_entry];
				$entry_uri_url = explode("/", $entry_uri);
				$entry_uri = str_replace($entry_uri_url[count($entry_uri_url)-2], "cloned-" . $entry_uri_url[count($entry_uri_url)-2], $entry_uri);

				unset($pages[1]['uris'][$entry_id]);
				$pages[1]['uris'][$entry_id] = $entry_uri;

				$pages = base64_encode(serialize($pages));

		        ee()->db->set('site_pages', $pages);
		        ee()->db->where('site_id', ee()->config->item('site_id'));
		        ee()->db->update('sites');
		    }
		}
	}
}