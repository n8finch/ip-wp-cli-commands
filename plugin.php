<?php
/**
 * Plugin Name: IP WP-CLI Commands
 * Version: 0.1
 * Plugin URI: https://n8finch.com/
 * Description: Some rando wp-cli commands to make life easier...
 * Author: Nate Finch
 * Author URI: https://n8finch.com/
 * Text Domain: ip-wpcli
 * Domain Path: /languages/
 * License: GPL v3
 */

 if ( !defined( 'WP_CLI' ) && WP_CLI ) {
	 //Then we don't want to load the plugin
     return;
 }


 /**
  * Implements IP WP-CLI commands.
  */
class IP_WP_CLI_COMMANDS extends WP_CLI_Command {

	/**
	 * Remove a PMPro level from a user
	 *
	 * ## OPTIONS
	 * --level=<number>
	 * : PMPro level to check for and remove
	 *
	 * --email=<email>
	 * : Email of user to check against
	 *
	 * [--dry-run]
	 * : Run the entire search/replace operation and show report, but don't save
	 * changes to the database.
	 *
	 * ## EXAMPLES
	 *
	 * wp ip remove_user --level=5 --email=me@test.com,another@email.com, and@another.com
	 *
	 * @synopsis --level=<number> --email=<email> [--dry-run]
	 *
	 * @when after_wp_load
	 */
	public function remove_user ( $args, $assoc_args ) {

		//Keep a tally of warnings and loops
		$total_warnings = 0;
		$total_users_removed = 0;
		$dry_suffix = '';
		$emails_not_existing = array();
		$emails_without_level = array();

		//Get the args
		$dry_run = $assoc_args['dry-run'];
		$level = $assoc_args['level'];
		$emails = explode( ',', $assoc_args['email'] );

		//loop through emails
		foreach ( $emails as $email ) {

			//Get User ID
			$user_id = email_exists($email);

			if( !$user_id ) {

				WP_CLI::warning( "The user {$email} does not seem to exist." );

				array_push( $emails_not_existing, $email );

				$total_warnings++;

				continue;
			}

			//Check membership level
			$has_level = pmpro_hasMembershipLevel( $level, $user_id );
			if ( $has_level ) {

				if ( !$dry_run ) {
					pmpro_cancelMembershipLevel( $level, $user_id, 'inactive' );
				}

				WP_CLI::success( "Membership canceled for {$email}, Level {$level} removed" . PHP_EOL );

				$total_users_removed++;

			} else {

				WP_CLI::warning( "The user {$email} does not have Level = {$level} membership." );

				array_push( $emails_without_level, $email );

				$total_warnings++;
			}

			//echo something to show that things are processing.
			system("echo ". "Member processed...");
			$total_loops++;
		} //end foreach

		if ( $dry_run ) {

			$dry_suffix = 'BUT, nothing really changed because this was a dry run:-).';

		}

		WP_CLI::success( "{$total_users_removed} User/s been removed, with {$total_warnings} warnings. {$dry_suffix}" );


		if ( $total_warnings ) {

			$emails_not_existing = implode(',', $emails_not_existing);
			$emails_without_level = implode(',', $emails_without_level);

			WP_CLI::warning(

				"These are the emails to double check and make sure things are on the up and up:" . PHP_EOL .
				"Non-existent emails: " . $emails_not_existing . PHP_EOL .
				"Emails without the associated level: " . $emails_without_level . PHP_EOL
			);

		}
	}
}

 WP_CLI::add_command( 'ip', 'IP_WP_CLI_COMMANDS' );
