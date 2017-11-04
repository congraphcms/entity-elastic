<?php
/*
 * This file is part of the cookbook/eav package.
 *
 * (c) Nikola Plavšić <nikolaplavsic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return array(
	/**
	 * List of data types supported by application
	 * 
	 * Each data type need to have unique key that will be used by cookbook administration
	 * to create suitable directive for input of this type
	 * 
	 * Warning: 
	 * keys are written in database with each attribute creation
	 * every change to keys in this config file will produce error with attributes
	 * that were created earlier
	 * 
	 * Data type properties:
	 * label                       - Label that will be used as human readable description 
	 *                               for this data type
	 * table                       - database table in which values of this type will be written
	 * handler                     - full name of class that will be used as handler for 
	 *                               this attribute
	 * handler_name                - name of the handler that is used for laravel app container
	 * value_model                 - full name of eloquent class that is used as value model
	 * can_have_default_value      - boolean flag whether this data type can have default value
	 * can_be_required             - boolean flag whether this data type can be required
	 * can_be_unique               - boolean flag whether this data type can be unique
	 * can_be_filter               - boolean flag whether this data type can be filterable
	 * can_be_localized            - boolean flag whether this data type can be localized
	 * has_options                 - boolean flag whether this data type can have options
	 * is_relation                 - boolean flag whether this data type is relation
	 * is_asset                    - boolean flag whether this data type is asset
	 * has_multiple_values         - boolean flag whether this data type have multiple values (array of values)
	 * 
	 * 
	 * Check documentation for what is needed to develop for one data type
	 */
	'field_types' => array(

		/**
		 * Simple text input
		 * 
		 * Administration will render this input as HTML5 text input
		 * values will be written as strings in attribute_values_text table
		 * 
		 * It's an open field that can be required, unique, sortable and filterable
		 */
		'text' => array(
			'label'						=> 'Text',
			'table' 					=> 'attribute_values_text',
			'handler'					=> 'Cookbook\Eav\Fields\Text\TextFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Text\TextFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Text\TextFieldValidator',
			'handler_name'				=> 'TextFieldHandler',
			'can_have_default_value'	=> true,
			'can_be_unique'				=> true,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> true,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),

		'tags' => array(
			'label'						=> 'Tags',
			'table' 					=> 'attribute_values_text',
			'handler'					=> 'Cookbook\Eav\Fields\Text\TextFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Text\TextFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Text\TextFieldValidator',
			'handler_name'				=> 'TextFieldHandler',
			'can_have_default_value'	=> true,
			'can_be_unique'				=> true,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> true,
			'has_options'				=> false,
			'has_multiple_values'		=> true,
			'sortable'					=> true
		),

		'boolean' => array(
			'label'						=> 'Boolean',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Boolean\BooleanFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Boolean\BooleanFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Boolean\BooleanFieldValidator',
			'handler_name'				=> 'BooleanFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),

		/**
		 * Select input
		 * 
		 * Administration will render this input as HTML5 select input
		 * values will be written as integers (ID of selected option) 
		 * in attribute_values_integer table
		 * 
		 * It's a choise field that can be required and filterable
		 * It has options (options for HTML5 select)
		 */
		'select' => array(
			'label'						=> 'Select',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Select\SelectFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Select\SelectFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Select\SelectFieldValidator',
			'handler_name'				=> 'SelectFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> true,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),
		'multiselect' => array(
			'label'						=> 'Multiselect',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Select\SelectFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Select\SelectFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Select\SelectFieldValidator',
			'handler_name'				=> 'SelectFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> true,
			'has_multiple_values'		=> true,
			'sortable'					=> true
		),

		/**
		 * Integer field
		 */
		'integer' => array(
			'label'						=> 'Integer Number',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Integer\IntegerFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Integer\IntegerFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Integer\IntegerFieldValidator',
			'handler_name'				=> 'IntegerFieldHandler',
			'can_have_default_value'	=> true,
			'can_be_unique'				=> true,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),

		/**
		 * Decimal field
		 */
		'decimal' => array(
			'label'						=> 'Decimal Number',
			'table' 					=> 'attribute_values_decimal',
			'handler'					=> 'Cookbook\Eav\Fields\Decimal\DecimalFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Decimal\DecimalFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Decimal\DecimalFieldValidator',
			'handler_name'				=> 'DecimalFieldHandler',
			'can_have_default_value'	=> true,
			'can_be_unique'				=> true,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),

		/**
		 * Datetime field
		 */
		'datetime' => array(
			'label'						=> 'Date & Time',
			'table' 					=> 'attribute_values_datetime',
			'handler'					=> 'Cookbook\Eav\Fields\Datetime\DatetimeFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Datetime\DatetimeFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Datetime\DatetimeFieldValidator',
			'handler_name'				=> 'DatetimeFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> true,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> true
		),

		/**
		 * Relation field
		 */
		'relation' => array(
			'label'						=> 'Relation',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Relation\RelationFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Relation\RelationFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Relation\RelationFieldValidator',
			'handler_name'				=> 'RelationFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> false
		),
		'relation_collection' => array(
			'label'						=> 'Relations',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Relation\RelationFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Relation\RelationFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Relation\RelationFieldValidator',
			'handler_name'				=> 'RelationFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> true,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> true,
			'sortable'					=> false
		),

		/**
		 * Asset field
		 */
		'asset' => array(
			'label'						=> 'Asset',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Asset\AssetFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Asset\AssetFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Asset\AssetFieldValidator',
			'handler_name'				=> 'AssetFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> false,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> false
		),
		'asset_collection' => array(
			'label'						=> 'Assets',
			'table' 					=> 'attribute_values_integer',
			'handler'					=> 'Cookbook\Eav\Fields\Asset\AssetFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Asset\AssetFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Asset\AssetFieldValidator',
			'handler_name'				=> 'AssetFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> false,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> true,
			'sortable'					=> false
		),
		'location' => array(
			'label'						=> 'Location',
			'table' 					=> 'attribute_values_text',
			'handler'					=> 'Cookbook\Eav\Fields\Location\LocationFieldHandler',
			'elastic_handler'			=> 'Cookbook\EntityElastic\Fields\Location\LocationFieldHandler',
			'validator'					=> 'Cookbook\Eav\Fields\Location\LocationFieldValidator',
			'handler_name'				=> 'LocationFieldHandler',
			'can_have_default_value'	=> false,
			'can_be_unique'				=> false,
			'can_be_localized'			=> true,
			'can_be_filter'				=> false,
			'can_be_searchable'			=> false,
			'has_options'				=> false,
			'has_multiple_values'		=> false,
			'sortable'					=> false
		),
	)

);