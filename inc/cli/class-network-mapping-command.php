<?php

namespace Mercator\CLI;

use Mercator\Network_Mapping;
use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_Error;

class Network_Mapping_Command extends WP_CLI_Command {
	/**
	 * Display a list of mappings
	 *
	 * @param Network_Mapping[] $mappings Mapping objects to show
	 * @param array $options
	 */
	protected function display( $mappings, $options ) {
		$defaults = array(
			'format' => 'table',
			'fields' => array( 'id', 'domain', 'network', 'active' ),
		);
		$options = wp_parse_args( $options, $defaults );

		$mapper = function ( Network_Mapping $mapping ) {
			$data = array(
				'id'     => (int) $mapping->get_id(),
				'domain' => $mapping->get_domain(),
				'network'   => (int) $mapping->get_network_id(),
				'active' => $mapping->is_active() ? __( 'Active', 'mercator' ) : __( 'Inactive', 'mercator' ),
			);
			return apply_filters( 'mercator.cli.mapping.fields', $data, $mapping );
		};
		$display_items = Utils\iterator_map( $mappings, $mapper );

		$formatter = new Formatter( $options );
		$formatter->display_items( $display_items );
	}

	/**
	 * ## OPTIONS
	 *
	 * [<network>]
	 * : Network ID (defaults to current network, use `--url=...`)
	 *
	 * [--format=<format>]
	 * : Format to display as (table, json, csv, count)
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$id = empty( $args[0] ) ? get_current_site()->id : absint( $args[0] );

		$mappings = Network_Mapping::get_by_network( $id );

		if ( empty( $mappings ) ) {
			return;
		}

		$this->display( $mappings, $assoc_args );
	}

	/**
	 * Get a single mapping
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Mapping ID
	 *
	 * [--format=<format>]
	 * : Format to display as (table, json, csv, count)
	 */
	public function get( $args, $assoc_args ) {
		$mapping = Network_Mapping::get( $args[0] );

		if ( empty( $mapping ) ) {
			$mapping = new WP_Error( 'mercator.cli.mapping_not_found', __( 'Invalid mapping ID', 'mercator' ) );
		}

		if ( is_wp_error( $mapping ) ) {
			return WP_CLI::error( $mapping->get_error_message() );
		}

		$mappings = array( $mapping );
		$this->display( $mappings, $assoc_args );
	}

	/**
	 * Delete a single mapping
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Mapping ID
	 */
	public function delete( $args ) {
		$mapping = Network_Mapping::get( $args[0] );

		if ( empty( $mapping ) ) {
			$mapping = new WP_Error( 'mercator.cli.mapping_not_found', __( 'Invalid mapping ID', 'mercator' ) );
		}

		if ( is_wp_error( $mapping ) ) {
			return WP_CLI::error( $mapping->get_error_message() );
		}

		$result = $mapping->delete();
		if ( empty( $result ) || is_wp_error( $result ) ) {
			return WP_CLI::error( __( 'Could not delete mapping', 'mercator' ) );
		}
	}
}
