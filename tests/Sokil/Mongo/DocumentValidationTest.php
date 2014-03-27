<?php

namespace Sokil\Mongo;

class DocumentValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Sokil\Mongo\Collection
     */
    private static $collection;
    
    public static function setUpBeforeClass()
    {
        // connect to mongo
        $client = new Client('mongodb://127.0.0.1');
        
        // select database
        $database = $client->getDatabase('test');
        
        // select collection
        self::$collection = $database->getCollection('phpmongo_test_collection');
    }
    
    public function setUp() {
    }
    
    public function tearDown() {

    }
    
    public static function tearDownAfterClass() {
        self::$collection->delete();
    }
    
    public function testIsValid_RequiredField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'required')
            )));
        
        // required field empty
        $this->assertFalse($document->isValid());
        
        // required field set
        $document->set('some-field-name', 'some-value');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEquals()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 'some-value')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'some-wrong-value');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'some-value');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldNotEquals()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'not_equals', 'to' => 'some-value')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'some-wrong-value');
        $this->assertTrue($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'some-value');
        $this->assertFalse($document->isValid());
    }
    
    public function testIsValid_FieldInRange()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'in', 'range' => array('acceptedValue1', 'acceptedValue2'))
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'acceptedValue1');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_NumericField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'numeric')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_NullField()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'numeric')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', null);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEqualsOnScenario()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 23, 'on' => 'SCENARIO_1,SCENARIO_2')
            )));
        
        // required field empty
        $document->set('some-field-name', 'wrongValue');
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->setScenario('SCENARIO_1');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEqualsExceptScenario()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'equals', 'to' => 23, 'except' => 'SCENARIO_1,SCENARIO_2')
            )));
        
        // required field empty
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
        
        // set excepted scenario
        $document->setScenario('SCENARIO_2');
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertTrue($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 23);
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldRegexp()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'regexp', 'pattern' => '#[a-z]+[0-9]+[a-z]+#')
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // required field set to wrong value
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // required field set to valid value
        $document->set('some-field-name', 'abc123def');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldEmail()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'email'),
                array('some-field-name-mx', 'email', 'mx' => true)
            )));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // Email invalid
        $document->set('some-field-name', 'wrongValue');
        $this->assertFalse($document->isValid());
        
        // Email valid
        $document->set('some-field-name', 'user@example.com');
        $this->assertTrue($document->isValid());
        
        // additional MX check on wrong email
        $document->set('some-field-name-mx', 'user@example.com');
        $this->assertFalse($document->isValid());
        
        // additional MX check on wrong email
        $document->set('some-field-name-mx', 'user@gmail.com');
        $this->assertTrue($document->isValid());
        
    }
    
    public function testIsValid_FieldValidatedByMethod_Passed()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules', 'somePassedValidationMethod'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'somePassedValidationMethod'),
            )));
        
        $document    
            ->expects($this->any())
            ->method('somePassedValidationMethod')
            ->will($this->returnValue(true));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // Field invalid
        $document->set('some-field-name', 'value');
        $this->assertTrue($document->isValid());
    }
    
    public function testIsValid_FieldValidatedByMethod_Failed()
    {
        // mock of document
        $document = $this->getMock('\Sokil\Mongo\Document', array('rules', 'someFailedValidationMethod'), array(self::$collection));
        $document
            ->expects($this->any())
            ->method('rules')
            ->will($this->returnValue(array(
                array('some-field-name', 'someFailedValidationMethod'),
            )));
        
        $document
            ->expects($this->any())
            ->method('someFailedValidationMethod')
            ->will($this->returnValue(false));
        
        // required field empty
        $this->assertTrue($document->isValid());
        
        // Field invalid
        $document->set('some-field-name', 'value');
        $this->assertFalse($document->isValid());
    }
    
    public function testTriggerError()
    {
        try {
            $document = new \Sokil\Mongo\Document(self::$collection);
            $document->triggerError('field', 'rule', 'message');
            
            $document->validate();
            
            $this->fail('\Sokil\Mongo\Document\Exception\Validate must be thrown, no exception captured');
        }
        catch (\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals(
                array('field' => array('rule' => 'message')), 
                $document->getErrors()
            );
        }
        catch(\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\Exception\Validate expected, ' . get_class($e) . ' found');
        }
        
    }
    
    public function testTriggerErrors()
    {
        $errors = array(
            'field1' => array('rule1' => 'message1'),
            'field2' => array('rule2' => 'message2')
        );
        
        try {
            $document = new \Sokil\Mongo\Document(self::$collection);
            $document->triggerErrors($errors);
            
            $document->validate();
            
            $this->fail('\Sokil\Mongo\Document\Exception\Validate must be thrown, no exception captured');
        }
        catch (\Sokil\Mongo\Document\Exception\Validate $e) {
            $this->assertEquals($errors, $document->getErrors());
        }
        catch(\Exception $e) {
            $this->fail('\Sokil\Mongo\Document\Exception\Validate expected, ' . get_class($e) . ' found');
        }
    }
}