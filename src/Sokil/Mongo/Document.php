<?php

namespace Sokil\Mongo;

use \Symfony\Component\EventDispatcher\EventDispatcher;

use Sokil\Mongo\Behavior;

class Document extends Structure
{
    const FIELD_TYPE_DOUBLE                   = 1;
    const FIELD_TYPE_STRING                   = 2;
    const FIELD_TYPE_OBJECT                   = 3;
    const FIELD_TYPE_ARRAY                    = 4;
    const FIELD_TYPE_BINARY_DATA              = 5;
    const FIELD_TYPE_UNDEFINED                = 6;  // deprecated
    const FIELD_TYPE_OBJECT_ID                = 7;
    const FIELD_TYPE_BOOLEAN                  = 8;
    const FIELD_TYPE_DATE                     = 9;
    const FIELD_TYPE_NULL                     = 10;
    const FIELD_TYPE_REGULAR_EXPRESSION       = 11;
    const FIELD_TYPE_JAVASCRIPT               = 13;
    const FIELD_TYPE_SYMBOL                   = 14;
    const FIELD_TYPE_JAVASCRIPT_WITH_SCOPE    = 15;
    const FIELD_TYPE_INT32                    = 16;
    const FIELD_TYPE_TIMESTAMP                = 17;
    const FIELD_TYPE_INT64                    = 18;
    const FIELD_TYPE_MIN_KEY                  = 255;
    const FIELD_TYPE_MAX_KEY                  = 127;
    
    const RELATION_HAS_ONE      = 'HAS_ONE';
    const RELATION_BELONGS      = 'BELONGS';
    const RELATION_HAS_MANY     = 'HAS_MANY';

    private $_resolvedRelations = array();
    
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private $_collection;
    
    protected $_scenario;
    
    /**
     *
     * @var array validator errors
     */
    private $_errors = array();
    
    /**
     *
     * @var array manually added validator errors
     */
    private $_triggeredErors = array();
    
    private $_eventDispatcher;
    
    /**
     *
     * @var \Sokil\Mongo\Operator
     */
    private $_operator;
    
    private $_behaviors = array();
    
    public function __construct(Collection $collection, array $data = null)
    {
        $this->_collection = $collection;
        
        $this->_init();
        
        $this->beforeConstruct();
        
        parent::__construct($data);
        
        $this->_eventDispatcher->dispatch('afterConstruct');
    }
    
    public function beforeConstruct() {}
    
        
    public function reset()
    {
        // reset structure
        parent::reset();
        
        // reset errors
        $this->_errors          = array();
        $this->_triggeredErors  = array();
        
        // reset behaviors
        $this->clearBehaviors();
        
        // init delegates
        $this->_init();
        
        return $this;
    }
    
    private function _init()
    {
        $this->_eventDispatcher = new EventDispatcher;
        $this->_operator        = new Operator;
        
        $this->attachBehaviors($this->behaviors());
    }
    
    public function __toString()
    {
        return (string) $this->getId();
    }
    
    public function __call($name, $arguments) {
        
        // behaviors
        foreach($this->_behaviors as $behavior) {
            if(!method_exists($behavior, $name)) {
                continue;
            }
            
            return call_user_func_array(array($behavior, $name), $arguments);
        }
    }
    
    public function __get($name)
    {
        $relations = $this->relations();
        
        if(isset($this->_resolvedRelations[$name])) {
            // relation already resolved
            return $this->_resolvedRelations[$name];
        } elseif(isset($relations[$name])) {
            // resolve relation
            return $this->_resolveRelation($name);
        } else {
            // get document parameter
            return parent::__get($name);
        }
    }
    
        
    /**
     * 
     * @return array relation description
     */
    public function relations()
    {
        // [relationName => [relationType, targetCollection, reference], ...]
        return array();
    }

    /**
     * Load relation
     * @param string $name name of relation
     */
    private function _resolveRelation($name)
    {
        $relations  = $this->relations();
        $relation   = $relations[$name];
        
        $relationType           = $relation[0];
        $targetCollectionName   = $relation[1];
        
        switch($relationType) {
            case self::RELATION_HAS_ONE:
                $sourceField = '_id';
                $targetField = $relation[2];
                
                $this->_resolvedRelations[$name] = $this->_collection
                    ->getDatabase()
                    ->getCollection($targetCollectionName)
                    ->find()
                    ->where($targetField, $this->get($sourceField))
                    ->findOne();
                    
                break;
            
            case self::RELATION_BELONGS:
                $sourceField = $relation[2];
                $targetField = '_id';
                
                $this->_resolvedRelations[$name] = $this->_collection
                    ->getDatabase()
                    ->getCollection($targetCollectionName)
                    ->find()
                    ->where($targetField, $this->get($sourceField))
                    ->findOne();
                
                break;
            
            case self::RELATION_HAS_MANY:
                $sourceField = '_id';
                $targetField = $relation[2];
                
                $this->_resolvedRelations[$name] = $this->_collection
                    ->getDatabase()
                    ->getCollection($targetCollectionName)
                    ->find()
                    ->where($targetField, $this->get($sourceField))
                    ->findAll();
                
                break;
        }
        
        return $this->_resolvedRelations[$name];
    }
    
    public function triggerEvent($event)
    {
        $this->_eventDispatcher->dispatch($event);
        return $this;
    }
    
    public function attachEvent($event, $handler)
    {
        $this->_eventDispatcher->addListener($event, $handler);
        return $this;
    }
    
    public function onAfterConstruct($handler)
    {
        $this->_eventDispatcher->addListener('afterConstruct', $handler);
        return $this;
    }
    
    public function onBeforeInsert($handler)
    {
        $this->_eventDispatcher->addListener('beforeInsert', $handler);
        return $this;
    }
    
    public function onAfterInsert($handler)
    {
        $this->_eventDispatcher->addListener('afterInsert', $handler);
        return $this;
    }
    
    public function onBeforeUpdate($handler)
    {
        $this->_eventDispatcher->addListener('beforeUpdate', $handler);
        return $this;
    }
    
    public function onAfterUpdate($handler)
    {
        $this->_eventDispatcher->addListener('afterUpdate', $handler);
        return $this;
    }
    
    public function onBeforeSave($handler)
    {
        $this->_eventDispatcher->addListener('beforeSave', $handler);
        return $this;
    }
    
    public function onAfterSave($handler)
    {
        $this->_eventDispatcher->addListener('afterSave', $handler);
        return $this;
    }
    
    public function onBeforeDelete($handler)
    {
        $this->_eventDispatcher->addListener('beforeDelete', $handler);
        return $this;
    }
    
    public function onAfterDelete($handler)
    {
        $this->_eventDispatcher->addListener('afterDelete', $handler);
        return $this;
    }
    
    public function getId()
    {
        return $this->get('_id');
    }
    
    /**
     * Used to define id of stored document. This id must be already presenf in db
     * 
     * @param type $id
     * @return \Sokil\Mongo\Document
     */
    public function defineId($id) {
        
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        $this->_data['_id'] = $id;
        
        return $this;
    }
    
    /*
     * Used to define id of unstord document. This db is manual
     */
    public function setId($id) {
        
        if(!($id instanceof \MongoId)) {
            $id = new \MongoId($id);
        }
        
        $this->set('_id', $id);
        
        return $this;
    }
    
    public function isStored()
    {
        return $this->get('_id') && !$this->isModified('_id');
    }
    
    public function setScenario($scenario)
    {
        $this->_scenario = $scenario;

        return $this;
    }

    public function getScenario()
    {
        return $this->_scenario;
    }

    public function setNoScenario()
    {
        $this->_scenario = null;

        return $this;
    }

    public function isScenario($scenario)
    {
        return $scenario === $this->_scenario;
    }
    
    public function rules()
    {
        return array();
    }
    
    /**
     * check if filled model params is valid
     * @return boolean
     */
    public function isValid()
    {
        $this->_errors = array();

        foreach($this->rules() as $rule)
        {
            $fields = array_map('trim', explode(',', $rule[0]));

            // check scenario
            if(!empty($rule['on'])) {
                $onScenarios = explode(',', $rule['on']);
                if(!in_array($this->getScenario(),  $onScenarios)) {
                    continue;
                }
            }

            if(!empty($rule['except'])) {
                $exceptScenarios = explode(',', $rule['except']);
                if(in_array($this->getScenario(),  $exceptScenarios)) {
                    continue;
                }
            }

            // validate
            switch($rule[1]) {
                case 'required':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" required in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'equals':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if($this->get($field) !== $rule['to']) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must be equals to "' . $rule['to'] . '" in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
                    
                case 'not_equals':

                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if($this->get($field) === $rule['to']) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must not be equals to "' . $rule['to'] . '" in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'in':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }

                        if(!in_array($this->get($field), $rule['range'])) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not in range of alloved values in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'numeric':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }
                        
                        if(!is_numeric($this->get($field))) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not numeric in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'null':
                    foreach($fields as $field) {                        
                        if(null !== $this->get($field)) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" must be null in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;

                case 'regexp':
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }

                        if(!preg_match($rule['pattern'], $this->get($field))) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not match regexp ' . $rule['pattern'] . ' in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
                    
                case 'email':
                    foreach($fields as $field) {
                        $value = $this->get($field);
                        if(!$value) {
                            continue;
                        }

                        $isValidEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                        $isValidMX = true;
                        
                        if($isValidEmail && !empty($rule['mx'])) {
                            list(, $host) = explode('@', $value);
                            $isValidMX =  checkdnsrr($host, 'MX');
                        }
                        
                        if(!$isValidEmail || !$isValidMX) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Value of field "' . $field . '" is not email in model ' . get_called_class();
                            }
                            
                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
                    
                default:
                    
                    foreach($fields as $field) {
                        if(!$this->get($field)) {
                            continue;
                        }

                        if(!method_exists($this, $rule[1])) {
                            continue;
                        }

                        // params, passed to rule method
                        $params = $rule;
                        unset($params[0]); // remove field list
                        unset($params[1]); // remove rule name
                        
                        if(!call_user_func(array($this, $rule[1]), $field, $params)) {
                            if(!isset($rule['message'])) {
                                $rule['message'] = 'Field "' . $field . '" not valid in model ' . get_called_class();
                            }

                            $this->_errors[$field][$rule[1]] = $rule['message'];
                        }
                    }
                    break;
            }
        }

        return !$this->hasErrors();
    }
    
    /**
     * 
     * @throws \Sokil\Mongo\Document\Exception\Validate
     */
    public function validate()
    {
        if(!$this->isValid()) {
            $exception = new \Sokil\Mongo\Document\Exception\Validate('Document not valid');
            $exception->setDocument($this);
            
            throw $exception;
        }
    }

    public function hasErrors()
    {
        return ($this->_errors || $this->_triggeredErors);
    }

    /**
     * get list of validation errors
     *
     * Format: $errors['fieldName']['rule'] = 'mesage';
     *
     * @return array list of validation errors
     */
    public function getErrors()
    {
        return array_merge_recursive($this->_errors, $this->_triggeredErors);
    }

    public function triggerError($fieldName, $rule, $message)
    {
        $this->_triggeredErors[$fieldName][$rule] = $message;
        return $this;
    }

    public function triggerErrors(array $errors)
    {
        $this->_triggeredErors = array_merge_recursive($this->_triggeredErors, $errors);
        return $this;
    }
    
    public function behaviors()
    {
        return array();
    }
    
    public function attachBehaviors(array $behaviors)
    {
        foreach($behaviors as $name => $behavior) {
            
            if(!($behavior instanceof Behavior)) {
                if(empty($behavior['class'])) {
                    throw new Exception('Behavior class not specified');
                }

                $className = $behavior['class'];
                unset($behavior['class']);

                $behavior = new $className($behavior);
            }
            
            $this->attachBehavior($name, $behavior);
        }
        
        return $this;
    }
    
    public function attachBehavior($name, Behavior $behavior)
    {
        $this->_behaviors[$name] = $behavior;
        
        return $this;
    }
    
    public function clearBehaviors()
    {
        $this->_behaviors = array();
        return $this;
    }
    
    public function getOperator()
    {
        return $this->_operator;
    }
    
    /**
     * Update value in local cache and in DB
     * 
     * @param type $fieldName
     * @param type $value
     * @return \Sokil\Mongo\Document
     */
    public function set($fieldName, $value)
    {
        parent::set($fieldName, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->_operator->set($fieldName, $value);
        }
        
        return $this;
    }
    
    public function unsetField($fieldName)
    {
        parent::unsetField($fieldName);
        
        if($this->getId() && $this->has($fieldName)) {
            $this->_operator->unsetField($fieldName);
        }
        
        return $this;
    }
    
    /**
     * @deprecated use self::merge() instead
     * @param array $data
     * @return \Sokil\Mongo\Structure
     */
    public function fromArray(array $data)
    {
        $this->merge($data);
        return $this;
    }
    
    public function merge(array $data)
    {        
        if($this->isStored()) {
            foreach($data as $fieldName => $value) {
                $this->set($fieldName, $value);
            }
        }
        else {
            parent::merge($data);
        }
        
        return $this;
    }
    
    public function append($fieldName, $value)
    {
        parent::append($fieldName, $value);
        
        // if document saved - save through update
        if($this->getId()) {
            $this->_operator->set($fieldName, $this->get($fieldName));
        }
        
        return $this;
    }
    
    /**
     * Push argument as single element to field value
     * 
     * @param string $fieldName
     * @param mixed $value
     */
    public function push($fieldName, $value)
    {
        $oldValue = $this->get($fieldName);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_operator->push($fieldName, $value);
            }
            $value = array($value);
        }
        // field already exist and has single value
        elseif(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, array($value));
            if($this->getId()) {
                $this->_operator->set($fieldName, $value);
            }
        }
        // field exists and is array
        else {
            if($this->getId()) {
                // check if array because previous $set operation on single value was executed
                $setValue = $this->_operator->get('$set', $fieldName);
                if($setValue) {
                    $setValue[] = $value;
                    $this->_operator->set($fieldName, $setValue);
                }
                else {
                    $this->_operator->push($fieldName, $value);
                }
                
            }
            $value = array_merge($oldValue, array($value));
        }
        
        // update local data
        parent::set($fieldName, $value);
        
        return $this;
    }
    
    /**
     * Push each element of argument's array as single element to field value
     * 
     * @param type $fieldName
     * @param array $value
     */
    public function pushFromArray($fieldName, array $value)
    {
        $oldValue = $this->get($fieldName);
        
        if($value instanceof Structure) {
            $value = $value->toArray();
        }
        
        // field not exists
        if(!$oldValue) {
            if($this->getId()) {
                $this->_operator->push($fieldName, array('$each' => $value));
            }
            
        }
        // field already exist and has single value
        else if(!is_array($oldValue)) {
            $value = array_merge((array) $oldValue, $value);
            if($this->getId()) {
                $this->_operator->set($fieldName, $value);
            }
        }
        // field already exists and is array
        else {
            if($this->getId()) {
                $this->_operator->push($fieldName, array('$each' => $value));
            }
            $value = array_merge($oldValue, $value);
        }
        
        // update local data
        parent::set($fieldName, $value);
    }
    
    /**
     * 
     * @param string $fieldName
     * @param integer|string|array|\Sokil\Mongo\Expression $expression
     * @return \Sokil\Mongo\Document
     */
    public function pull($fieldName, $expression)
    {
        $this->_operator->pull($fieldName, $expression);
        return $this;
    }
    
    public function increment($fieldName, $value = 1)
    {
        parent::set($fieldName, (int) $this->get($fieldName) + $value);
        
        if($this->getId()) {
            $this->_operator->increment($fieldName, $value);
        }

        
        return $this; 
    }
    
    public function decrement($fieldName, $value = 1)
    {
        return $this->increment($fieldName, -1 * $value);
    }
    
    public function save($validate = true)
    {
        $this->_collection->saveDocument($this, $validate);
        return $this;
    }
    
    public function delete()
    {
        $this->_collection->deleteDocument($this);
    }
}