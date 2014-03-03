<?php

class SearchableBehavior extends ModelBehavior {

    /**
     * Behavior settings
     *
     * @var array
     */
    public $settings = array();

    /**
     * Search Model handler
     *
     * @var object
     */
    public $Search = null;

    /**
     * Behavior setup
     *
     * @param object $Model
     * @param array $settings Fields to be indexed. Defaults to model's displayField
     */
    function setup(Model $Model, $settings = array()) {
        
        // Settings
        if (!isset($this->settings[$Model->alias])) {
            $this->settings[$Model->alias] = array(
                'fields' => array($Model->displayField),
            );
        }

        $this->settings[$Model->alias] = array_merge(
                $this->settings[$Model->alias], (array) $settings);

        // Search Model init
        $this->Search = ClassRegistry::init('MultiModelSearch.Search');
    }

    /**
     * Saves the new search index data for this record
     *
     * @param object $Model Model
     */
    function afterSave(Model $Model, $created, $options = Array()) {
        if (!$Model->id or !$data = $this->buildIndex($Model)) {
            return;
        }

        $this->Search->saveIndex($Model->alias, $Model->id, $data);
    }

    /**
     * Deletes the search index data for this record
     *
     * @param object $Model
     * @return boolean True if success, false if failure
     */
    function beforeDelete(Model $Model, $config = Array()) {
        if (!$Model->id) {
            return false;
        }

        $conditions = array(
            'model' => $Model->alias,
            'model_id' => $Model->id,
        );

        $this->Search->deleteAll($conditions, false, true);

        return true;
    }

    /**
     * Build index field value, to be saved as a string in the search_index table.
     *
     * @param object $Model
     * @return mixed Returns false if fields to be indexed are not in $Model->data, 
     * or returns a string ready to be saved in the search_index table. 
     */
    function buildIndex(Model $Model, $config = Array()) {
        // $Model->data must be set
        if (!$data = $Model->data[$Model->alias]) {
            return false;
        }

        $fields = $this->settings[$Model->alias]['fields'];

        if (!is_array($fields)) {
            $fields = array($fields);
        }

        // All fields must be in $Model->data
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        $chunks = array();

        foreach ($data as $field => $value) {
            if (in_array($field, $fields)) {
                $chunks[] = $value;
            }
        }
        
        $index = join(' ', $chunks);

        // Cleaning
        $index = html_entity_decode($index, ENT_COMPAT, 'UTF-8');
        $index = strip_tags($index);

        return $index;
    }

}
