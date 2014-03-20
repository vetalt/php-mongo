<?php

namespace Sokil\Mongo;

class DocumentTest extends \PHPUnit_Framework_TestCase
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
    
    public function testToString()
    {
        $document = self::$collection->createDocument(array(
            'param1'    => 'value1'
        ));
        
        self::$collection->saveDocument($document);
        
        $this->assertEquals((string) $document, $document->getId());
    }
        
    public function testCreateDocumentFromArray()
    {
        $document = self::$collection->createDocument(array(
            'param1'    => 'value1',
            'param2'    => array(
                'param21'   => 'value21',
                'param22'   => 'value22',
            )
        ));
        
        $this->assertEquals('value1', $document->get('param1'));
        $this->assertEquals('value22', $document->get('param2.param22'));
    }
    
    public function testSetId()
    {
        // save document
        $id = new \MongoId();
        
        $doc = self::$collection->createDocument(array('a' => 'a'));
        $doc->setId($id);
        self::$collection->saveDocument($doc);
        
        // find document
        $this->assertNotEmpty(self::$collection->getDocument($id));
        
        // delete document
        self::$collection->deleteDocument($doc);
        
    }
    
    public function testIsStored()
    {
        // not stored
        $document = self::$collection->createDocument(array('k' => 'v'));
        $this->assertFalse($document->isStored());
        
        // stored
        self::$collection->saveDocument($document);
        $this->assertTrue($document->isStored());
    }
    
    public function testIsStoredWhenIdSet()
    {
        // not stored
        $document = self::$collection->createDocument(array('k' => 'v'));
        $document->setId(new \MongoId);
        $this->assertFalse($document->isStored());
        
        // stored
        self::$collection->saveDocument($document);
        $this->assertTrue($document->isStored());
    }
    
    public function testSet()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->set('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->set('a.b.c', 'value2');
        $this->assertEquals('value2', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value2'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value2')), $document->get('a'));
        
        self::$collection->saveDocument($document);
        $document = self::$collection->getDocument($document->getId());
        
        $document->set('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->set('a.b.c', 'value2');
        $this->assertEquals('value2', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value2'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value2')), $document->get('a'));
    }

    public function testSetObject()
    {
        $obj = new \stdclass;
        $obj->param = 'value';
        
        // save
        $document = self::$collection->createDocument()
            ->set('d', $obj)
            ->save();
        
        $this->assertEquals(
            (array) $obj, 
            $document->d
        );
        
        $this->assertEquals(
            (array) $obj, 
            self::$collection->getDocumentDirectly($document->getId())->d
        );
    }
    
    public function testSetDate()
    {
        $date = new \MongoDate;
        
        // save
        $document = self::$collection->createDocument()
            ->set('d', $date)
            ->save();
        
        $this->assertEquals(
            $date, 
            $document->d
        );
        
        $this->assertEquals(
            $date, 
            self::$collection->getDocumentDirectly($document->getId())->d
        );
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnNewDoc()
    {
        $doc = self::$collection->createDocument(array(
            'a' => 1,
        ));
        
        $doc->set('a.b', 2);
        $this->assertEquals(array('a' => array('b' => 2)), $doc->toArray());
    }
    
    public function testSetMongoCode()
    {
        $doc = self::$collection->createDocument(array(
            'code'  => new \MongoCode('Math.sin(45);'),
        ))->save();
        
        $this->assertInstanceOf(
            '\MongoCode',
            self::$collection->getDocumentDirectly($doc->getId())->code
        );
    }
    
    public function testSetMongoRegex()
    {
        $doc = self::$collection->createDocument(array(
            'code'  => new \MongoRegex('/[a-z]/'),
        ))->save();
        
        $this->assertInstanceOf(
            '\MongoRegex',
            self::$collection->getDocumentDirectly($doc->getId())->code
        );
    }
    
    /**
     * @expectedException \Sokil\Mongo\Exception
     */
    public function testSetArrayToScalarOnExistedDoc()
    {
        $doc = self::$collection
            ->createDocument(array(
                'a' => 1,
            ))
            ->save();
        
        $doc->set('a.b', 2)->save();
    }
        
    public function testUnsetInNewDocument()
    {
        $doc = self::$collection->createDocument(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a21'   => 1,
                    'a22'   => 2,
                ),
            )
        ));
        
        $doc->unsetField('a.a2.a21');
        
        $this->assertEquals(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a22'   => 2,
                ),
            )
        ), $doc->toArray());
    }
    
    public function testUnsetInExistedDocument()
    {
        $doc = self::$collection
            ->createDocument(array(
                'a' => array(
                    'a1'    => array(
                        'a11'   => 1,
                        'a12'   => 2,
                    ),
                    'a2'    => array(
                        'a21'   => 1,
                        'a22'   => 2,
                    ),
                )
            ))
            ->save();
        
        $doc->unsetField('a.a2.a21')->save();
        
        $data = $doc->toArray();
        unset($data['_id']);
        
        $this->assertEquals(array(
            'a' => array(
                'a1'    => array(
                    'a11'   => 1,
                    'a12'   => 2,
                ),
                'a2'    => array(
                    'a22'   => 2,
                ),
            )
        ), $data);
    }
    
    public function testAppend()
    {
        $document = self::$collection->createDocument(array(
            'param' => 'value',
        ));
        
        $document->append('a.b.c', 'value1');
        $this->assertEquals('value1', $document->get('a.b.c'));
        $this->assertEquals(array('c' => 'value1'), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => 'value1')), $document->get('a'));
        
        $document->append('a.b.c', 'value2');
        $this->assertEquals(array('value1', 'value2'), $document->get('a.b.c'));
        $this->assertEquals(array('c' => array('value1', 'value2')), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => array('value1', 'value2'))), $document->get('a'));
        
        self::$collection->saveDocument($document);
        $document = self::$collection->getDocument($document->getId());
        
        $document->append('a.b.c', 'value3');
        $this->assertEquals(array('value1', 'value2', 'value3'), $document->get('a.b.c'));
        $this->assertEquals(array('c' => array('value1', 'value2', 'value3')), $document->get('a.b'));
        $this->assertEquals(array('b' => array('c' => array('value1', 'value2', 'value3'))), $document->get('a'));
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
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementNotExistedKeyOfUnsavedDocument()
    {
        /**
         * Increment unsaved
         */
        $doc = self::$collection
            ->createDocument(array('i' => 1));
        
        // increment
        $doc->increment('j', 2);
        $doc->increment('j', 4);
        
        // test
        $this->assertEquals(6, $doc->get('j'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementNotExistedKeyOfSavedDocument()
    {
        $doc = self::$collection
            ->createDocument(array('i' => 1))
            ->save();
        
        /**
         * Increment saved
         */
        $doc->increment('j', 2); // existed key
        $doc->increment('j', 4);
        
        // test
        $this->assertEquals(6, $doc->get('j'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(6, $doc->get('j'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementExistedKeyOfUnsavedDocument()
    {
        /**
         * Increment unsaved
         */
        $doc = self::$collection
            ->createDocument(array('i' => 1));
        
        // increment
        $doc->increment('i', 2);
        $doc->increment('i', 4);
        
        // test
        $this->assertEquals(7, $doc->get('i'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }
    
    /**
     * @covers \Sokil\Mongo\Document::increment
     */
    public function testIncrementExistedKeyOfSavedDocument()
    {
        $doc = self::$collection
            ->createDocument(array('i' => 1))
            ->save();
        
        /**
         * Increment saved
         */
        $doc->increment('i', 2); // existed key
        $doc->increment('i', 4);
        
        // test
        $this->assertEquals(7, $doc->get('i'));
        
        // save
        $doc->save();
        
        /**
         * Test increment of document in cache
         */
        $doc = self::$collection->getDocument($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
        
        /**
         * Test increment after reread from db
         */
        $doc = self::$collection->getDocumentDirectly($doc->getId());
        $this->assertEquals(7, $doc->get('i'));
    }
    
    public function testPushNumberToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to empty
        $doc->push('key', 1);
        $doc->push('key', 2);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1, 2), self::$collection->getDocument($doc->getId())->key);
    }

    public function testPushObjectToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        $object1 = new \stdclass;
        $object2 = new \stdclass;
        
        // push single to empty
        $doc->push('key', $object1);
        $doc->push('key', $object2);
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(
            array((array)$object1, (array)$object2), 
            $doc->key
        );
        
        $this->assertEquals(
            array((array)$object1, (array)$object2), 
            self::$collection->getDocumentDirectly($doc->getId())->key
        );
    }
    
    public function testPushMongoIdToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection
            ->createDocument(array(
                'some' => 'some',
            ))
            ->save();
        
        $id = new \MongoId;
        
        // push single to empty
        $doc
            ->push('key', $id)
            ->save();
        
        $this->assertEquals(array($id), $doc->key);
        
        $this->assertEquals(array($id), self::$collection->getDocumentDirectly($doc->getId())->key);
    }
    
    public function testPushArrayToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to empty
        $doc->push('key', array(1));
        $doc->push('key', array(2));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array(1),array(2)), self::$collection->getDocument($doc->getId())->key);
        
    }
    
    public function testPushArrayToEmptyOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push array to empty
        $doc->push('key', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array(1)), self::$collection->getDocument($doc->getId())->key);
    }
    
    public function testPushSingleToSingleOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push single to single
        $doc->push('some', 'another1');
        $doc->push('some', 'another2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another1', 'another2'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushSingleToSingleOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to single
        $doc->push('some', 'another1');
        $doc->push('some', 'another2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another1', 'another2'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToSingleOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to single
        $doc->push('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', array('another')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToSingleOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        // push array to single
        $doc->push('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', array('another')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushSingleToArrayOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push single to array
        $doc->push('some', 'some3');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', 'some3'), self::$collection->getDocument($doc->getId())->some);
        
    }
    
    public function testPushArrayToArrayOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->push('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', array('some3')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushArrayToArrayOnNewDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        // push array to array
        $doc->push('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', array('some3')), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPushFromArrayToEmptyOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to empty
        $doc->pushFromArray('key', array(1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(1), self::$collection->getDocument($doc->getId())->key);
        
    }
    
    public function testPushFromArrayToSingleOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => 'some',
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to single
        $doc->pushFromArray('some', array('another'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some', 'another'), self::$collection->getDocument($doc->getId())->some);
        
    }
    
    public function testPushFromArrayToArrayOnExistedDocument()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pushFromArray('some', array('some3'));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array('some1', 'some2', 'some3'), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPullFromOneDimensionalArray()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array('some1', 'some2'),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', 'some2');
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(
            array('some1'), 
            $doc->some
        );
        
        $this->assertEquals(
            array('some1'), 
            self::$collection->getDocument($doc->getId())->some
        );
    }
    
    public function testPullFromTwoDimensionalArray()
    {
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array('sub'  => 1), 
                array('sub'  => 2)
            ),
        ));
        
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', array(
            'sub'  => 2
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(array('sub' => 1)), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPullFromThreeDimensionalArray()
    {
        self::$collection->delete();
        
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array(
                    'sub'  => array(
                        array('a' => 1),
                        array('b' => 2),
                    )
                ),
                array(
                    'sub'  => array(
                        array('a' => 3),
                        array('b' => 4),
                    )
                )
            ),
        ));
        self::$collection->saveDocument($doc);
        
        // pull 1
        $doc->pull('some', array(
            'sub.a'  => 1
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), self::$collection->getDocument($doc->getId())->some);
        
        // pull 2
        $doc->pull('some', array(
            'sub'  => array(
                'a' => 3,
            )
        ));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(), self::$collection->getDocument($doc->getId())->some);
    }
    
    public function testPullFromThreeDimensionalUsingExpressionArray()
    {
        self::$collection->delete();
        
        // create document
        $doc = self::$collection->createDocument(array(
            'some' => array(
                array(
                    'sub'  => array(
                        array('a' => 1),
                        array('b' => 2),
                    )
                ),
                array(
                    'sub'  => array(
                        array('a' => 3),
                        array('b' => 4),
                    )
                )
            ),
        ));
        self::$collection->saveDocument($doc);
        
        // push array to array
        $doc->pull('some', self::$collection->expression()->where('sub.a', 1));
        self::$collection->saveDocument($doc);
        
        $this->assertEquals(array(
            array(
                'sub'  => array(
                    array('a' => 3),
                    array('b' => 4),
                )
            )
        ), self::$collection->getDocument($doc->getId())->some);
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
    
    public function testFromArrayOnUpdate()
    {
        // save document
        $document = self::$collection->createDocument(array(
            'p' => 'pv',
        ));
        self::$collection->saveDocument($document);
           
        // update document
        $document
            ->set('f1', 'fv1')
            ->fromArray(array(
                'a1'    => 'av1',
                'a2'    => 'av2',
            ));
        
        $documentData = $document->toArray();
        unset($documentData['_id']);
        
        $this->assertEquals(array(
            'p'     => 'pv',
            'f1'    => 'fv1',
            'a1'    => 'av1',
            'a2'    => 'av2',
        ), $documentData);
        
        self::$collection->saveDocument($document);
        
        // test
        $foundDocumentData = self::$collection->getDocumentDirectly($document->getId())
            ->toArray();
        
        unset($foundDocumentData['_id']);
        
        $this->assertEquals(array(
            'p'     => 'pv',
            'f1'    => 'fv1',
            'a1'    => 'av1',
            'a2'    => 'av2',
        ), $foundDocumentData);
    }
    
    public function testExecuteBehavior()
    {
        $document = self::$collection->createDocument(array('param' => 0));
        
        $document->attachBehavior('get42', new \Sokil\Mongo\DocumentTest\SomeBehavior());
        
        $this->assertEquals(42, $document->return42());
    }
}