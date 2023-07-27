VZ Address
==========

OMG Update to VZ Address with a few alterations

### Additional Tag Variables ###

Inside the fieldtype output loop

* `coords_x` - Output the latitude coordinate
* `coords_y` - Output the longitude coordinate
* `coords` - Outputs both coordinates, separated by a comma

### New Settings ###

These new settings are helpful in executing distance calculations

* `Dump Latitude Coordinates to Field` - Select a text field in the same field group to dump the latitude coordinate to
* `Dump Longitude Coordinates to Field` - Select a text field in the same field group to dump the longitude coordinate to

NOTE: These currently don't work properly before the entry is saved the first time. When saving a new entry, immediately save again to populate these fields.

### New Addon Settings ###

A field for an API key for static maps was also added to the VZ Address addon settings. This is currently automatically populated but can be changed if need be.