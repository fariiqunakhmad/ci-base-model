<?php
/**
 * An extension of CodeIgniter's base Model class to remove repetition and increase productivity by providing
 * a couple handy methods(powered by CI's query builder), validation-in-model support, event callbacks and more.
 *
 * @author  Jamie Rumbelow <http://jamierumbelow.net>
 * @author  Md Emran Hasan <phpfour@gmail.com>
 * @author  Roni Kumar Saha <roni.cse@gmail.com>
 * @version 2.0
 *
 * @link    http://github.com/jamierumbelow/codeigniter-base-model
 * @link    https://github.com/phpfour/MY_Model
 * @link    https://github.com/ronisaha/ci-base-model
 *
 */

use Mockery as m;

require_once 'tests/support/test_helper.php';
require_once 'tests/support/models/CI_My_model.php';
require_once 'tests/support/models/after_callback_model.php';
require_once 'tests/support/models/before_callback_model.php';
require_once 'tests/support/models/blamable_model.php';
require_once 'tests/support/models/callback_parameter_model.php';
require_once 'tests/support/models/primary_key_model.php';
require_once 'tests/support/models/protected_attributes_model.php';
require_once 'tests/support/models/record_model.php';
require_once 'tests/support/models/relationship_model.php';
require_once 'tests/support/models/serialised_data_model.php';
require_once 'tests/support/models/soft_delete_model.php';
require_once 'tests/support/models/timestamp_model.php';
require_once 'tests/support/models/validated_model.php';

class CI_Base_Model_tests extends PHPUnit_Framework_TestCase
{
    public $model;

    /* --------------------------------------------------------------
     * TEST INFRASTRUCTURE
     * ------------------------------------------------------------ */

    public function setUp()
    {
        $this->model = new Record_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');
    }

    public function tearDown()
    {
        unset($this->model);
    }

    /* --------------------------------------------------------------
     * GENERIC METHODS
     * ------------------------------------------------------------ */

    public function test_constructor_guesses_the_table_name()
    {
        $this->model = new Record_model();

        $this->assertEquals($this->model->get_table(), 'records');
    }

    public function test_constructor_guess_the_primary_key()
    {
        $this->model = new Book_model($this->model->_database, 'id');

        $this->model->_database->expects($this->once())
            ->method('query')
            ->with($this->equalTo("SHOW KEYS FROM `books` WHERE Key_name = 'PRIMARY'"))
            ->will($this->returnValue($this->model->_database))
        ;

        $this->model->_database->expects($this->once())
            ->method('dbprefix')
            ->with($this->equalTo('books'))
            ->will($this->returnValue('books'))
        ;

        $this->model->_database->expects($this->once())
            ->method('row')
            ->will($this->returnValue((object)array('Column_name'=>'fake_primary_key')));

        $this->model = $this->model->__construct($this->model->_database);

        $this->assertEquals($this->model->primary_key(), 'fake_primary_key');
    }

    /* --------------------------------------------------------------
     * CRUD INTERFACE
     * ------------------------------------------------------------ */

    public function test_get()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->get(2), 'fake_record_here');
    }

    public function test_get_by()
    {
        $this->_mock_get_by_call();

        $this->assertEquals($this->model->get_by('some_column', 'some_value'), 'fake_record_here');
    }

    public function test_get_by_alias()
    {
        $this->_mock_get_by_call();

        $this->assertEquals($this->model->getBy('some_column', 'some_value'), 'fake_record_here');
    }

    public function test_get_many()
    {
        $this->model->_database->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), $this->equalTo(array(1, 2, 3, 4, 5)))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_many(array(1, 2, 3, 4, 5)), array('fake', 'records', 'here'));
    }

    public function test_get_many_by()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_many_by('some_column', 'some_value'), array('fake', 'records', 'here'));
    }

    public function test_get_all()
    {
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake', 'records', 'here')));

        $this->assertEquals($this->model->get_all(), array('fake', 'records', 'here'));
    }

    private function _expect_fake_find()
    {
        $this->model->_database->expects($this->once())
            ->method('where')
            ->with($this->equalTo(array('some_column'=>'1')));

        $this->_expect_get();
        $this->model->_database->expects($this->once())
            ->method('result')
            ->will($this->returnValue(array('fake', 'records', 'here')));
    }

    public function test_find()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->find(array('some_column'=>'1')), 'fake');
    }

    public function test_find_all()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->find_all(array('some_column'=>'1')), array('fake', 'records', 'here'));
    }

    public function test_find_all_in_camel_case_form()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->findAll(array('some_column'=>'1')), array('fake', 'records', 'here'));
    }

    public function test_find_by()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->find_by('some_column','1'), 'fake');
    }

    public function test_find_by_in_camel_case_form()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->findBy('some_column','1'), 'fake');
    }

    public function test_find_by_some_column()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->find_by_some_column('1'), 'fake');
    }

    public function test_find_by_some_column_in_camel_case_form()
    {
        $this->_expect_fake_find();
        $this->assertEquals($this->model->findBySomeColumn('1'), 'fake');
    }

    public function test_insert()
    {
        $this->model->_database->expects($this->once())
                        ->method('insert')
                        ->with($this->equalTo('records'), $this->equalTo(array('new' => 'data')));
        $this->model->_database->expects($this->any())
                        ->method('insert_id')
                        ->will($this->returnValue(123));

        $this->assertEquals($this->model->insert(array('new' => 'data')), 123);
    }

    public function test_insert_many_with_individual_insert()
    {
        $this->model->_database->expects($this->exactly(2))
                        ->method('insert')
                        ->with($this->equalTo('records'));
        $this->model->_database->expects($this->any())
                        ->method('insert_id')
                        ->will($this->returnValue(123));

        $this->assertEquals($this->model->insert_many(array(array('new' => 'data'), array('other' => 'data')), true, true), array(123, 123));
    }

    public function test_insert_many_with_batch_insert()
    {
        $this->model->_database->expects($this->once())
            ->method('insert_batch')
            ->with($this->equalTo('records'), $this->equalTo(array(array('new' => 'data'), array('other' => 'data'))))
            ->will($this->returnValue(123));

        $this->assertEquals($this->model->insert_many(array(array('new' => 'data'), array('other' => 'data'))), 123);
    }

    public function test_update()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update(2, array('new' => 'data')), TRUE);
    }

    public function test_update_many()
    {
        $this->model->_database->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), $this->equalTo(array(1, 2, 3, 4, 5)))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update_many(array(1, 2, 3, 4, 5), array('new' => 'data')), TRUE);
    }

    public function test_update_by()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update_by('some_column', 'some_value', array('new' => 'data')), TRUE);
    }

    public function test_update_all()
    {
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo(array('new' => 'data')))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->update_all(array('new' => 'data')), TRUE);
    }

    public function test_update_batch()
    {
        $this->model->_database->expects($this->once())
            ->method('update_batch')
            ->with($this->equalTo('records'), $this->equalTo(array(array('new' => 'data'), array('other' => 'data'))),$this->equalTo('new'))
            ->will($this->returnValue(123));

        $this->assertEquals($this->model->update_batch(array(array('new' => 'data'), array('other' => 'data')), 'new'), 123);
    }

    public function test_on_duplicate_update()
    {
        $data = array('new' => 'data');


        $this->model->_database->expects($this->once())
            ->method('escape')
            ->with($this->equalTo('data'))
            ->will($this->returnValue('data'));

        $this->model->_database->expects($this->once())
            ->method('query')
            ->with($this->anything())
            ->will($this->returnValue(123));

        $this->assertEquals($this->model->on_duplicate_update($data, $data), 123);
    }

    public function test_delete()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete(2), TRUE);
    }

    public function test_delete_by()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_by('some_column', 'some_value'), TRUE);
    }

    public function test_delete_many()
    {
        $this->model->_database->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), array(1, 2, 3, 4, 5))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('delete')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_many(array(1, 2, 3, 4, 5)), TRUE);
    }

    /* --------------------------------------------------------------
     * MORE CALLBACK TESTS
     * ------------------------------------------------------------ */  

    public function test_before_create_callbacks()
    {
        $this->model = new Before_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $row = array( 'one' => 'ONE', 'two' => 'TWO' );
        $expected_row = array( 'one' => 'ONE', 'two' => 'TWO', 'key' => 'Value', 'another_key' => '123 Value' );

        $this->model->_database->expects($this->once())
                        ->method('insert')
                        ->with($this->equalTo('records'), $this->equalTo($expected_row));

        $this->model->insert($row);
    }

    public function test_after_create_callbacks()
    {
        $this->model = new After_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('insert_id')
                        ->will($this->returnValue(10));

        $self =& $this;

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->insert(array( 'row' => 'here' ));
        }, 10);
    }

    public function test_before_update_callbacks()
    {
        $this->model = new Before_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $row = array( 'one' => 'ONE', 'two' => 'TWO' );
        $expected_row = array( 'one' => 'ONE', 'two' => 'TWO', 'key' => 'Value', 'another_key' => '123 Value' );

        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo($expected_row))
                        ->will($this->returnValue($this->model->_database));

        $this->model->update(1, $row);
    }

    public function test_after_update_callbacks()
    {
        $this->model = new After_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('set')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('update')->will($this->returnValue(TRUE));

        $self =& $this;

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->update(1, array( 'row' => 'here' ));
        }, array( array( 'row' => 'here' ), true ));
    }

    public function test_before_get_callbacks()
    {
        $this->model = new Before_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $self =& $this;

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->get(1);
        }, NULL);
    }

    public function test_after_get_callbacks()
    {
        $this->model = new After_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $db_row = array( 'one' => 'ONE', 'two' => 'TWO' );
        $expected_row = array( 'one' => 'ONE', 'two' => 'TWO', 'key' => 'Value', 'another_key' => '123 Value' );

        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('get')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('row')->will($this->returnValue($db_row));

        $this->assertEquals($expected_row, $this->model->get(1));
    }

    public function test_before_delete_callbacks()
    {
        $this->model = new Before_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');
        
        $self =& $this;

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->delete(12);
        }, 12);
    }

    public function test_after_delete_callbacks()
    {
        $this->model = new After_callback_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');
        
        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('delete')->will($this->returnValue(TRUE));

        $self =& $this;

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->delete(9);
        }, TRUE);
    }

    public function test_callbacks_support_parameters()
    {
        $this->model = new Callback_parameter_model();

        $self =& $this;
        $callback_parameters = array(
            'some_param', 'another_param'
        );

        $this->assertCallbackIsCalled(function() use ($self)
        {
            $self->model->some_method();
        }, $callback_parameters);
    }

    /**
     * Callbacks, if called in an array, should receive a "last" boolean
     * when they're in the last iteration of triggering - the last row in a result
     * array, for instance - for clearing things up
     */
    public function test_callbacks_in_iteration_have_last_variable()
    {
        // stub
    }

    /* --------------------------------------------------------------
     * PROTECTED ATTRIBUTES
     * ------------------------------------------------------------ */ 

    public function test_protected_attributes()
    {
        $this->model = new Protected_attributes_model();
        
        $author = array(
            'id' => 123,
            'hash' => 'dlkadflsdasdsadsds',
            'title' => 'A new post'
        );
        $author_obj = (object)$author;

        $author = $this->model->protect_attributes($author);
        $author_obj = $this->model->protect_attributes($author_obj);

        $this->assertFalse(isset($author['id']));
        $this->assertFalse(isset($author['hash']));
        $this->assertFalse(isset($author_obj->id));
        $this->assertFalse(isset($author_obj->hash));
    }

    /* --------------------------------------------------------------
     * RELATIONSHIPS
     * ------------------------------------------------------------ */

    public function test_belongs_to()
    {
        $object = (object)array( 'title' => 'A Post', 'created_at' => time(), 'author_id' => 43 );
        $author_object = (object)array( 'id' => 43, 'name' => 'Jamie', 'age' => 20 );
        $expected_object = (object)array( 'title' => 'A Post', 'created_at' => time(), 'author_id' => 43, 'author' => $author_object );

        $self =& $this;

        $this->model = new Belongs_to_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');
        $this->model->load = $this->getMock('MY_Model_Mock_Loader');

        $author_model = new Author_modelCI();
        $author_model->_database = $this->getMockBuilder('MY_Model_Mock_DB')->setMockClassName('Other_Mock_MY_Model_Mock_DB')->getMock();

        $author_model->_database->expects($this->once())->method('where')->will($this->returnValue($author_model->_database));
        $author_model->_database->expects($this->once())->method('get')->will($this->returnValue($author_model->_database));

        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('get')->will($this->returnValue($this->model->_database));

        $this->model->_database->expects($this->once())->method('row')->will($this->returnValue($object));
        $author_model->_database->expects($this->once())->method('row')->will($this->returnValue($author_object));

        $this->model->load->expects($this->once())->method('model')->with('author_model', 'author_model')
                          ->will($this->returnCallback(function() use ($self, $author_model){
                              $self->model->author_model = $author_model;
                          }));

        $this->assertEquals($expected_object, $this->model->with('author')->get(1));
    }

    public function test_has_many()
    {
        $object = (object)array( 'id' => 1, 'title' => 'A Post', 'created_at' => time(), 'author_id' => 43 );
        
        $comment_object = (object)array( 'id' => 1, 'comment' => 'A comment' );
        $comment_object_2 = (object)array( 'id' => 2, 'comment' => 'Another comment' );

        $expected_object = (object)array( 'id' => 1, 'title' => 'A Post', 'created_at' => time(), 'author_id' => 43,
                                          'comments' => array( $comment_object, $comment_object_2 ) );

        $self =& $this;

        $this->model = new Belongs_to_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');
        $this->model->load = $this->getMock('MY_Model_Mock_Loader');

        $comment_model = new Author_modelCI();
        $comment_model->_database = $this->getMockBuilder('MY_Model_Mock_DB')->setMockClassName('Another_Mock_MY_Model_Mock_DB')->getMock();

        $comment_model->_database->expects($this->once())->method('where')->with('comment_id', 1)->will($this->returnValue($comment_model->_database));
        $comment_model->_database->expects($this->once())->method('get')->will($this->returnValue($comment_model->_database));

        $this->model->_database->expects($this->once())->method('where')->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())->method('get')->will($this->returnValue($this->model->_database));

        $this->model->_database->expects($this->once())->method('row')->will($this->returnValue($object));
        $comment_model->_database->expects($this->once())->method('result')->will($this->returnValue(array( $comment_object, $comment_object_2 )));

        $this->model->load->expects($this->once())->method('model')->with('comment_model', 'comments_model')
                          ->will($this->returnCallback(function() use ($self, $comment_model){
                              $self->model->comments_model = $comment_model;
                          }));

        $this->assertEquals($expected_object, $this->model->with('comments')->get(1));
    }

    public function test_relate_works_with_objects_and_arrays()
    {
        $data = array( 'name' => 'Jamie', 'author_id' => 1 );
        $author = 'related object';

        $this->model = new Belongs_to_model();
        $this->model->author_model = m::mock(new Author_modelCI());
        $this->model->author_model->shouldReceive('get')
                                  ->andReturn($author);

        $obj = $this->model->with('author')->relate((object)$data);
        $arr = $this->model->with('author')->relate($data);
        
        $this->assertInternalType('object', $obj);
        $this->assertInternalType('array', $arr);
        $this->assertTrue(isset($obj->author));
        $this->assertTrue(isset($arr['author']));
        $this->assertEquals($author, $obj->author);
        $this->assertEquals($author, $arr['author']);
    }

    /* --------------------------------------------------------------
     * VALIDATION
     * ------------------------------------------------------------ */    

    public function test_validate_correctly_returns_the_data_on_success_and_FALSE_on_failure()
    {
        $this->model = $this->_validatable_model();
        $data = array( 'name' => 'Jamie', 'sexyness' => 'loads' );

        $this->assertEquals($this->model->validate($data), $data);

        $this->model = $this->_validatable_model(FALSE);
        $this->assertEquals($this->model->validate($data), FALSE);
    }

    public function test_skip_validation()
    {
        $data = array( 'name' => 'Jamie', 'sexyness' => 'loads' );
        $this->model = new Validated_model();
        $this->model->form_validation = m::mock('form_validation_class');
        $this->model->form_validation->shouldReceive('run')->never();

        $this->model->skip_validation();
        $this->assertEquals($this->model->validate($data), $data);
    }

    public function test_skip_validation_settings()
    {
        $ret = $this->model->skip_validation();

        $this->assertEquals($ret, $this->model);
        $this->assertAttributeEquals(TRUE, 'skip_validation', $this->model);

        $ret = $this->model->enable_validation();
        $this->assertEquals($ret, $this->model);

        $this->assertAttributeEquals(FALSE, 'skip_validation', $this->model);

    }

    protected function _validatable_model($validate_pass_or_fail = TRUE)
    {
        $model = new Validated_model();
        $model->form_validation = m::mock('form_validation_class');
        $model->form_validation->shouldIgnoreMissing();
        $model->form_validation->shouldReceive('run')
                               ->andReturn($validate_pass_or_fail);

        return $model;
    }

    /* --------------------------------------------------------------
     * SOFT DELETE
     * ------------------------------------------------------------ */    

    public function test_soft_delete()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo('deleted_at'), $this->equalTo('NOW()'), $this->equalTo(FALSE))
                        ->will($this->returnValue($this->model->_database));

        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete(2), TRUE);
    }

    public function test_soft_delete_custom_key()
    {
        $this->model = new Soft_delete_model('record_deleted_at');
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo('record_deleted_at'), $this->equalTo('NOW()'), $this->equalTo(FALSE))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete(2), TRUE);
    }

    public function test_soft_delete_by()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('key'), $this->equalTo('value'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo('deleted_at'), $this->equalTo('NOW()'), $this->equalTo(FALSE))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_by('key', 'value'), TRUE);
    }

    public function test_soft_delete_many()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('where_in')
                        ->with($this->equalTo('id'), $this->equalTo(array(2, 4, 6)))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('set')
                        ->with($this->equalTo('deleted_at'), $this->equalTo('NOW()'), $this->equalTo(FALSE))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('update')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_many(array(2, 4, 6)), TRUE);
    }

    public function test_soft_delete_get()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->at(0))
                        ->method('where')
                        ->with($this->equalTo("(deleted_at > NOW() OR deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->at(1))
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->get(2), 'fake_record_here');
    }

    public function test_soft_delete_dropdown()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $fake_row_1 = array( 'id' => 1, 'name' => 'Jamie' );
        $fake_row_2 = array( 'id' => 2, 'name' => 'Laura' );
        $fake_results = array( (object)$fake_row_1, (object)$fake_row_2 );

        $this->model->_database->expects($this->at(0))
                        ->method('where')
                        ->with($this->equalTo("(deleted_at > NOW() OR deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"))
                        ->will($this->returnValue($this->model->_database));
        
        $this->model->_database->expects($this->once())
                        ->method('select')
                        ->with($this->equalTo(array('id', 'name')))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->any())
                        ->method('result')
                        ->will($this->returnValue($fake_results));
        
        $this->model->dropdown('name');
    }

    public function test_with_deleted()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->exactly(1))
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->with_deleted()->get(2), 'fake_record_here');
    }

    public function test_only_deleted()
    {
        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo("`deleted_at` <= NOW()"))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('result')
                        ->will($this->returnValue(array('fake_record_here')));
        
        $this->assertEquals($this->model->only_deleted()->get_all(), array('fake_record_here'));
    }

    public function test_soft_delete_future_delete()
    {
        $whenToDelete = new \DateTime();
        $whenToDelete = $whenToDelete->modify('+1 day')->format('Y-m-d H:i:s');

        $this->model = new Soft_delete_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $this->model->_database->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id'), $this->equalTo(2))
            ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
            ->method('set')
            ->with($this->equalTo('deleted_at'), $this->equalTo($whenToDelete), $this->equalTo(TRUE))
            ->will($this->returnValue($this->model->_database));

        $this->model->_database->expects($this->once())
            ->method('update')
            ->with($this->equalTo('records'))
            ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->delete_at(2, $whenToDelete), TRUE);
    }

    /* --------------------------------------------------------------
     * CALLBACKS
     * ------------------------------------------------------------ */

    public function test_serialize()
    {
        $this->model = new Serialised_data_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $data = array( 'name' => 'Jamie', 'awesomeness_level' => 1000000 );

        $this->model->_database->expects($this->exactly(1))
                        ->method('insert')
                        ->with($this->equalTo('records'), $this->equalTo(array( 'data' => serialize($data) )));

        $this->model->insert(array( 'data' => $data ));
    }

    public function test_timestamps()
    {
        $this->model = new Timestamp_model();

        $data = array( 'name' => 'Jamie' );
        $obj = (object)array( 'name' => 'Jamie' );
        
        $data = $this->model->created_at($data);
        $obj = $this->model->created_at($obj);
        $data = $this->model->updated_at($data);
        $obj = $this->model->updated_at($obj);

        $this->assertTrue(isset($data['created_at']));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $data['created_at']);
        $this->assertTrue(isset($obj->created_at));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $obj->created_at);
        $this->assertTrue(isset($data['updated_at']));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $data['updated_at']);
        $this->assertTrue(isset($obj->updated_at));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $obj->updated_at);
    }

    public function test_blamable()
    {
        $this->model = new Timestamp_model();
        $this->model->_database = $this->getMock('MY_Model_Mock_DB');

        $obj = (object)array( 'name' => 'Jamie' );

        $updateObject = (object)array('id'=>1, 'name' => 'Jamie' );

        $this->model->_database->expects($this->once())
            ->method('insert')
            ->with($this->equalTo('records'), $this->equalTo($obj));
        $this->model->_database->expects($this->any())
            ->method('insert_id')
            ->will($this->returnValue(2));

        $this->model->_database->expects($this->once())
            ->method('where')
            ->with($this->equalTo('id'), $this->equalTo(1))
            ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
            ->method('set')
            ->with($this->equalTo($updateObject))
            ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
            ->method('update')
            ->with($this->equalTo('records'))
            ->will($this->returnValue(TRUE));

        $this->assertEquals($this->model->insert($obj), 2);
        $this->assertEquals($this->model->update(1, $updateObject), true);


        $this->assertTrue(isset($obj->created_at));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $obj->created_at);

        $this->assertTrue(isset($obj->updated_at));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $obj->updated_at);

        $this->assertTrue(isset($updateObject->updated_at));
        $this->assertRegExp("/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/", $updateObject->updated_at);
    }

    /* --------------------------------------------------------------
     * UTILITY METHODS
     * ------------------------------------------------------------ */ 

    public function test_dropdown()
    {
        $fake_row_1 = array( 'id' => 1, 'name' => 'Jamie' );
        $fake_row_2 = array( 'id' => 2, 'name' => 'Laura' );

        $fake_results = array( (object)$fake_row_1, (object)$fake_row_2 );

        $this->model->_database->expects($this->once())
                        ->method('select')
                        ->with($this->equalTo(array('id', 'name')))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->any())
                        ->method('result')
                        ->will($this->returnValue($fake_results));

        $this->assertEquals($this->model->dropdown('name'), array( 1 => 'Jamie', 2 => 'Laura' ));
    }

    public function test_count_by()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('count_all_results')
                        ->will($this->returnValue(5));

        $this->assertEquals($this->model->count_by('some_column', 'some_value'), 5);
    }

    public function test_count_all()
    {
        $this->model->_database->expects($this->once())
                        ->method('count_all')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue(200));
        $this->assertEquals($this->model->count_all(), 200);
    }

    public function test_get_next_id()
    {
        $this->model->_database->database = 'some_database_name';

        $this->model->_database->expects($this->once())
                        ->method('select')
                        ->with($this->equalTo('AUTO_INCREMENT'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('from')
                        ->with($this->equalTo('information_schema.TABLES'))
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->any())
                        ->method('where')
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('get')
                        ->will($this->returnValue($this->model->_database));
        $this->model->_database->expects($this->once())
                        ->method('row')
                        ->will($this->returnValue((object)array( 'AUTO_INCREMENT' => 250 )));

        $this->assertEquals($this->model->get_next_id(), 250);
    }

    public function test_as_array()
    {
        $this->model->_database->expects($this->once())
                        ->method('where')
                        ->with($this->equalTo('id'), $this->equalTo(2))
                        ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
                        ->method('row_array')
                        ->will($this->returnValue('fake_record_here'));

        $this->assertEquals($this->model->as_array()->get(2), 'fake_record_here');
    }

    /* --------------------------------------------------------------
     * QUERY BUILDER DIRECT ACCESS METHODS
     * ------------------------------------------------------------ */ 

    public function test_order_by_regular()
    {
        $this->model->_database->expects($this->once())
                        ->method('order_by')
                        ->with($this->equalTo('some_column'), $this->equalTo('DESC'));

        $this->assertEquals($this->model->order_by('some_column', 'DESC'), $this->model);
    }

    public function test_order_by_array()
    {
        $this->model->_database->expects($this->once())
                        ->method('order_by')
                        ->with($this->equalTo('some_column'), $this->equalTo('ASC'));

        $this->assertEquals($this->model->order_by(array('some_column' => 'ASC')), $this->model);
    }

    public function test_limit()
    {
        $this->model->_database->expects($this->once())
                        ->method('limit')
                        ->with($this->equalTo(10), $this->equalTo(5));

        $this->assertEquals($this->model->limit(10, 5), $this->model);
    }

    public function test_truncate()
    {
        $this->model->_database->expects($this->once())
                        ->method('truncate')
                        ->with($this->equalTo('records'));

        $this->model->truncate();
    }

    /* --------------------------------------------------------------
     * TEST UTILITIES
     * ------------------------------------------------------------ */

    protected function _expect_get()
    {
        $this->model->_database->expects($this->once())
                        ->method('get')
                        ->with($this->equalTo('records'))
                        ->will($this->returnValue($this->model->_database));
    }

    protected function _list_fields()
    {
        $this->model->_database->expects($this->once())
                        ->method('list_fields')
                        ->with($this->equalTo($this->model->table()))
                        ->will($this->returnValue($this->model->get_fields()));
    }

    private function _mock_get_by_call()
    {
        $this->model->_database->expects($this->once())
            ->method('where')
            ->with($this->equalTo('some_column'), $this->equalTo('some_value'))
            ->will($this->returnValue($this->model->_database));
        $this->_expect_get();
        $this->model->_database->expects($this->once())
            ->method('row')
            ->will($this->returnValue('fake_record_here'));
    }

    /* --------------------------------------------------------------
     * CUSTOM ASSERTIONS
     * ------------------------------------------------------------ */

    public function assertCallbackIsCalled($method, $params = null)
    {
        try
        {
            $method();
            $this->fail('Callback wasn\'t called');
        }
        catch (Callback_Test_Exception $e)
        {
            if (!is_null($params))
            {
                $this->assertEquals($e->passed_object, $params);
            }
        }
    }
}