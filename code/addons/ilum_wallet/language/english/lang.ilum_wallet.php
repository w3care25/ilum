<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(
    
//General Info
"ilum_wallet"        		    => "Ilum Wallet",
"wallet_description"      			=> "API Endpoints and Tools for Ilum Wallet",
"wallet_docs_url"              		=> "https://bitbucket.org/omg-monkee/ilum-wallet/src/master/",

//Menu
'wallet_api_keys'                     => "API Keys",
'wallet_new_api_key'                  => "New API Key",
'wallet_settings'                   => "Settings",
'wallet_fields'                     => "Fields",
"wallet_error"                 		=> "Error!",
"wallet_warning"                    => "Warning!",
"wallet_btn_saving"            		=> "Saving",
"wallet_btn_save_settings"     		=> "Save",
"wallet_updated"               		=> "Settings Updated",
"wallet_documentation"				=> "Documentation",
"wallet_btn_generate_key"             => "Generate API Key",
"wallet_btn_generating"               => "Generating",
"wallet_btn_delete"                   => "Delete",
"wallet_btn_deleting"                 => "Deleting",
"wallet_email_templates"            => "Email Templates",

//Table
'site'                          => "Site",
'key'                           => "Key",
'status'                        => "Status",
'actions'                       => "Actions",
'api'                           => "API",
'endpoint'                      => "Endpoint",
'method'                        => "Method",
'route'                         => "Route URL",
'user'                          => "User",
'env'                           => "Environment",

//APIs
'proxibid'                      => "Proxibid",
'wavebid'                       => "Wavebid",

//New API Key
'wallet_site_url'                   => "Site URL",
'wallet_site_url_desc'              => "Enter the domain of the URL to generate a key for. Ex: omahamediagroup.com",

//Edit
'wallet_edit_key'                   => "Edit Key",
'wallet_activate'                   => "Activate",
'wallet_activate_desc'              => "Clicking Save will make this API Key <b>Active</b>!",
'wallet_deactivate'                 => "Deactivate",
'wallet_deactivate_desc'            => "Clicking Save will make this API Key <b>Inactive</b>!",

//Delete
'wallet_delete_key'               => "Delete Key",
'wallet_confirm_delete_key'       => "Are you sure you want to permantly delete <b>KEY</b> for <b>USER</b>?",
'wallet_confirm_delete_key_desc'  => "Deleting this key will also delete any Endpoint Routes associated with it. Deactivation is preferred, this is intended for mistakes",

//Settings
'wallet_ilum_api_user'           => "Ilum API User",
'wallet_ilum_api_key'            => "Ilum API Key",
'wallet_stripe_test_mode'           => "Stripe Test Mode",
'wallet_stripe_test_mode_desc'      => "If On, will use the Test Mode keys for Stripe",
'wallet_stripe_secret_test_key'     => "Stripe API Key - Secret Test Mode",
'wallet_stripe_publish_test_key'    => "Stripe API Key - Publishable Test Mode",
'wallet_stripe_secret_live_key'     => "Stripe API Key - Secret Live Mode",
'wallet_stripe_publish_live_key'    => "Stripe API Key - Publishable Live Mode",
'wallet_currency_ratio'             => "Ilum Bucks Currency Ratio",
'wallet_plaid_client_id'            => "Plaid Client ID",
'wallet_plaid_public_key'           => "Plaid Public Key",
'wallet_plaid_secret_key'           => "Plaid Secret Key",
'wallet_plaid_env'                  => "Plaid Environment",
'wallet_plaid_env_desc'             => "Which Plaid Environment should the keys use?",
'wallet_plaid_env_sandbox'          => "Sandbox",
'wallet_plaid_env_development'      => "Development",
'wallet_plaid_env_production'       => "Production",
'wallet_ip_api_key'                 => "IP API Key",
'wallet_minimum_age'                => "Minimum Age",
'wallet_minimum_age_desc'           => "Enter the number of years",
'wallet_paypal_account'             => "PayPal Account",
'wallet_paypal_logo'                => "PayPal Logo URL",
'wallet_paypal_color'               => "PayPal Color (no #)",
'wallet_paypal_pdt_token'           => "PayPal PDT Token",
'wallet_processing_percent'         => "Processing Percentage",
'wallet_processing_percent_desc'    => "What percentage of funds do you want to charge the user when Adding Funds (to cover processing fees)",
'wallet_processing_fee'             => "Processing Fee",
'wallet_processing_fee_desc'        => "What additional fee do you want to charge the user when Adding Funds (to cover processing fees)",

'wallet_ick_live_security_key'        => "ICK Security Key",
'wallet_ick_test_mode'        => "ICK Test Mode",
'wallet_ick_test_mode_desc'      => "If On, will use the Test Mode keys for ICK",
'wallet_ick_desc'      => "API Security Key assigned to a merchant account.",

'ick_new_card'      => "ICK New Card",

'ick_add_new_card'      => "Add New Card",
'ick_add_new_card_desc'      => "In publishing and graphic design, Lorem ipsum is a placeholder text commonly used to demonstrate the visual form of a document ",

//Environments
'wallet_env'                         => "Environment",
'wallet_env_production'              => "Production",
'wallet_env_development'             => "Development",
'wallet_env_sandbox'                 => "Sandbox",

//Fields
'wallet_first_name_field'           => "First Name Member Field",
'wallet_last_name_field'            => "Last Name Member Field",
'wallet_company_field'              => "Company Member Field",
'wallet_address_field'              => "Address Member Field",
'wallet_address2_field'             => "Address 2 Member Field",
'wallet_city_field'                 => "City Member Field",
'wallet_state_field'                => "State Member Field",
'wallet_zip_field'                  => "Zip Member Field",
'wallet_country_field'              => "Country Member Field",
'wallet_phone_field'                => "Phone Member Field",
'wallet_phone_email_field'          => "Phone Email Member Field",
'wallet_birth_day_field'            => "Birth Day Member Field",
'wallet_birth_month_field'          => "Birth Month Member Field",
'wallet_birth_year_field'           => "Birth Year Member Field",
'wallet_stripe_customer_id_field'   => "Stripe Customer ID Member Field",
'wallet_unique_id_field'            => "API Unique ID Member Field",
'wallet_app_header_field'           => "App Header Field",

//Email Templates
'wallet_emails_from_name'           => "From Name",
'wallet_emails_from_email'          => "From Email",
'wallet_emails_reply_to'            => "Reply to Email",
'wallet_emails_funds_added'         => "Funds Added",
'wallet_emails_new_payment_method'  => "New Payment Method",
'wallet_emails_payment_method_deleted' => "Payment Method Deleted",
'wallet_emails_new_default_payment_method' => "New Default Payment Method",
'wallet_emails_my_settings_updated' => "My Profile Updated",
'wallet_subject'                    => "Subject",
'wallet_text_email'                 => "Text Email",
'wallet_html_email'                 => "HTML Email",

//Alerts
"wallet_alert_updated"              => "API Key Settings Saved",
"wallet_alert_updated_desc"         => "The settings for <b>USER</b> have been saved successfully!",
"wallet_alert_generated"            => "API Key Generated",
"wallet_alert_generated_desc"       => "A New API Key has been generated for <b>USER</b>: API_KEY",
"wallet_alert_not_generated"        => "API Key Generation Failure",
"wallet_alert_not_generated_desc"   => "An issue was encountered while generating an API Key. Please try again.",
"wallet_alert_deleted"              => "API Key Deleted",
"wallet_alert_deleted_desc"         => "The API Key and it's corresponding Endpoint Routes have been permanently deleted!",
"wallet_alert_settings"             => "Settings Saved",
"wallet_alert_settings_desc"        => "The settings have been saved successfully!",

//Messages
'unauthorized'              => "Unauthorized - API Key Error",
'not_found'                 => "Not Found",
'success'                   => "Success",
'auth_not_found'            => "Not Found - No matching member exists",
'auth_fail'                 => "Credentials Failed",
'auth_success'              => "Credentials Accepted - Member Returned",
'auth_banned'               => "Banned User - This user is not allowed to access the system",
'wallet_insufficient_funds' => "Insufficient Funds",
'wallet_ilum_gift'       => "Ilum App Gift",
'wallet_refund'             => "Refund",
'wallet_funds_added'        => "Funds Added",
'wallet_funds_added_warning' => "NOTE: Funds may take a few minutes to show in your account when using third-party payment solutions",

//Register
'wallet_bad_request'         => "Invalid Request - There was a processing error!",
'wallet_register_not_acceptable' => "Must be at least 18 years of age to register",
'wallet_match_success'    => "User Successfully Matched",

//API Logout
'wallet_no_logout'          => "No Active Sessions Found",
'wallet_logout_success'     => "Active Sessions Terminated",

//Lang Function

//General
'wallet_wallet'             => "Wallet",
'wallet_onme'               => "Ilum",
'wallet_onme_wallet'        => "Ilum Wallet",
'wallet_ilum'            => "Ilum",
'wallet_my_profile'         => "My Profile",
'wallet_log_out'            => "Log Out",
'wallet_dashboard'          => "Dashboard",
'wallet_add_funds'          => "Add Funds",
'wallet_transfer'           => "Transfer",
'wallet_payment_methods'    => "Payment Methods",
"wallet_support"            => "Support",
'wallet_copyright'          => "Copyright",
'wallet_patent_pending'     => "Patent Pending",
'wallet_version'            => "v1.1.0",

//Stripe Charge Messages
'wallet_stripe_funds_added' => "Funds Added to Ilum Wallet",

//Transaction Types
'wallet_txn_type_1'         => "Funds Added to Wallet",

//Sign In
'wallet_sign_in'            => "Sign In",
'wallet_sign_in_desc'       => "For your protection, please verify your identity.",
'wallet_username_sent'      => "<strong>Your username request has been successfully sent.</strong> Check your email for a message with your Username. <b>NOTE: This email message may appear in your spam box.</b>",
'wallet_user_deleted'       => "<strong>Your account and associated content has been deleted!</strong> You can always create a new account at any time.",
'wallet_email'              => "Email",
'wallet_password'           => "Password",
'wallet_forgot_password'    => "Forgot Password?",
'wallet_register_here'      => "Not a member yet? Register Here",
'wallet_login_with'         => "Login with",

//Sign Up
'wallet_sign_up'            => "Sign Up",
'wallet_sign_up_desc'       => "Fill out the form below to become a member.",
'wallet_sign_up_social_desc' => "Your social media login was successful. Please complete the missing information to complete your registration!",
'wallet_first_name'         => "First Name",
'wallet_last_name'          => "Last Name",
'wallet_email'              => "Email",
'wallet_password'           => "Password",
'wallet_confirm_password'   => "Confirm Password",
'wallet_password_desc'      => "Password must be at least 8 characters long and contain at least one uppercase character, one lowercase character and one number/special character.",
'wallet_password_match'     => "Passwords must match!",
'wallet_accept_terms'       => "I have read and agree to the <a href='/legal/terms-of-use' target='_blank'>Terms of Use</a> and <a href='/legal/privacy-policy' target='_blank'>Privacy Policy</a>.",
'wallet_already_have_account' => "Already have an Account?",
'wallet_birthday'           => "Birthday",
'wallet_min_age'            => "Minimum Age: 18",

//Forgot Password
'wallet_forgot_password'    => "Forgot Password",
'wallet_forgot_password_desc'   => "Enter your e-mail below and we will send you reset instructions!",
'wallet_submit_request'     => "Submit Request",
'wallet_remember_password'  => "Remember your Password?",
'wallet_forgot_password_error' => "The email address you submitted does not exist or is invalid.",
'wallet_forgot_password_success' => "<strong>Your request has been successfully sent.</strong> Check your email for instructions on resetting your Password. <b>NOTE: This email message may appear in your spam box.</b>",

//Reset Password
'wallet_reset_password'     => "Reset Password",
'wallet_reset_password_desc' => "This form will allow you to setup your new password.",
'wallet_reset_password_success' => "<strong>Your Password has been successfully changed.</strong> You may now login with that Password.",
'wallet_invalid_token'      => "<strong>The reset token provided is invalid.</strong>",
'wallet_new_password'       => "New Password",
'wallet_confirm_new_password' => "Confirm New Password",
'wallet_ready_to_login'     => "Ready to",

//My Profile
'wallet_register_success'   => "Your account has been successfully <b>created</b>!",
'wallet_settings'           => "Settings",
'wallet_account_updated'    => "Your account has been successfully <b>updated</b>!",
'wallet_pass_updated'       => "Your password has been successfully <b>changed</b>!",
'wallet_email_updated'      => "Your email has been successfully <b>changed</b>! You should now log in with your new email address.",
'wallet_name'               => "Name",
'wallet_edit'               => "Edit",
'wallet_phone'              => "Phone",
'wallet_company'            => "Company",
'wallet_address'            => "Address",
'wallet_security'           => "Security",
'wallet_stats'              => "Stats",
'wallet_join_date'          => "Join Date",
'wallet_last_activity'      => "Last Activity",

//Edit Profile
'wallet_edit_profile'       => "Edit Profile",
'wallet_city'               => "City",
'wallet_state_region'       => "State / Region",
'wallet_zip_postal_code'    => "Zip / Postal Code",
'wallet_country'            => "Country",
'wallet_save_settings'      => "Save Settings",
'wallet_state_abbr'         => "State Abbreviation",
'wallet_notifications'      => "Notifications",
'wallet_yes'                => "Yes",
'wallet_no'                 => "No",

//Change Password
'wallet_change_password'    => "Change Password",
'wallet_current_password'   => "Current Password",
'wallet_current_password_desc' => "Enter your current password to confirm your changes.",
'wallet_start_typing_password' => "Start typing password",

//Change Email
'wallet_change_email'       => "Change Email",
'wallet_already_taken'      => "This email is already taken!",

//Dashboard
'wallet_add_funds_success'  => "Funds Transferred Successfully!",
'wallet_add_more_funds'     => "Add More Funds",
'wallet_balance'            => "Balance",
'wallet_no_transactions'    => "No Transactions",
'wallet_no_matching_transactions' => "No Matching Transactions",
'wallet_activity'           => "Activity",
'wallet_date'               => "Date",
'wallet_description_label'  => "Description",
'wallet_transaction_id'     => "TXN #",

//Add Funds
'wallet_payment_method'     => "Payment Method",
'wallet_amount'             => "Amount",
'wallet_ilum_bucks'         => "Ilum Bucks",
'wallet_will_add'           => "Will add",
'wallet_manage_payment_methods' => "Manage Payment Methods",
'wallet_add_funds_minimum'  => "Minimum of $1.00",
'wallet_add_funds_error'    => "An error was encountered while attempting to add the requested funds to your account.",
'wallet_insufficient_funds_txn_warning' => "You do not have enough Ilum Bucks to complete this transaction. Add enough to continue!",
'wallet_paypal'             => "PayPal",
'wallet_apple_pay'          => "ApplePay",
'wallet_back_to_ilum'       => "Back to My Ilum Wallet",
'wallet_add_funds_with_applepay' => "Add Funds",
'wallet_total_charge'       => "Total Charge",
'wallet_cancel'             => "Cancel",
'wallet_applepay_error'     => "Your Device is currently incompatible or improperly configured to use ApplePay. Please check your iOS Wallet settings and ensure you have an active Payment Method.",
'wallet_processing_fee'     => "Processing Fee",

//Transfer
'wallet_transfer_sub_title' => "Transfer your Ilum Bucks using these apps and integrations",
'wallet_transfer_ilum'   => "Ilum App",
'wallet_transfer_ilum_desc' => "Send gifts to friends and family using your Ilum Bucks!",
'wallet_transfer_no_results' => "There are currently no transfer methods. Please check back soon!",

//Payment Methods
'wallet_new_credit_card'    => "New Card",
'wallet_new_bank_account'   => "New Account",
'wallet_credit_card_added'  => "The  new <b>Credit Card</b> has been added to your Payment Methods",
'wallet_credit_card_failed' => "An error occurred while trying to add the Credit Card to your Payment Methods!",
'wallet_bank_account_added' => "The new <b>Bank Account</b> has been added to your Payment Methods",
'wallet_bank_account_failed' => "An error occurred while trying to add the Bank Account to your Payment Methods!",
'wallet_expired'            => "Expired",
'wallet_default'            => "Default",
'wallet_set_as_default'     => "Set as Default",
'wallet_delete_payment_method' => "Delete Payment Method",
'wallet_expiry'             => "Exp.",
'wallet_close'              => "Close",
'wallet_set_as_default_desc' => "Are you sure you want to set the following payment method as your default payment method?",
'wallet_delete_payment_method_desc' => "Are you sure you want to delete the following payment method from your account?",
'wallet_no_payment_methods' => "<b>There are currently no Payment Methods tied to your account.</b> Use the buttons above to add Credit Cards or Bank Accounts as your payment methods.",
'wallet_new_default_method' => "New Default Method",
'wallet_new_default_method_desc' => "You have successfully set a new default payment method.",
'wallet_new_default_method_error' => "An error was encountered while attempting to set a new default payment method.",
'wallet_token_deleted'      => "Payment Method Deleted",
'wallet_token_deleted_desc' => "The payment method was successfully deleted from your account!",
'wallet_token_deleted_error' => "An error was encountered while attempting to remove the payment method from you account.",
'wallet_payment_method_needed' => "Payment Method Needed",
'wallet_payment_method_needed_desc' => "Before you can add funds, you need to add a valid Payment Method",

//404
'wallet_oops'               => "Oops!",
'wallet_page_not_found'     => "404 - Page Not Found",
'wallet_cant_find_page'     => "We can't seem to find the page you're looking for",
'wallet_error_code'         => "Error Code:",

''=>''
); 

?>