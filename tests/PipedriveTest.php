<?php

namespace Zakirullin\Pipedrive\Tests;

use Zakirullin\Pipedrive\Pipedrive;
use Zakirullin\Pipedrive\Tests\Http\FakeHttpClient;

class PipedriveTest extends \PHPUnit_Framework_TestCase
{
    protected $pipedrive;
    protected $db;

    const ENTITIES = 30;
    const CHILD_ENTITIES = 5;

    public function setUp()
    {
        $this->db = $this->createDB();
        $this->pipedrive = new Pipedrive('xxx', ['organizations' => ['custom' => 'hash']], new FakeHttpClient($this->db));

        parent::setUp();
    }

    public function testGet()
    {
        $organization = $this->pipedrive->organizations->find(1)->one();

        $this->assertEquals(1, $organization->id);
        $this->assertEquals('organization1', $organization->name);
        $this->assertEquals('custom', $organization->custom);
    }

    public function testGetChained()
    {
        $notes = $this->pipedrive->organizations->persons->notes->all();

        for ($id = 1; $id < static::ENTITIES * static::CHILD_ENTITIES; $id++) {
            $this->assertEquals($id, $notes[$id]->id);
            $this->assertEquals("note$id", $notes[$id]->content);
        }
    }

    public function testGetChilds()
    {
        $notes = $this->pipedrive->organizations->find(1)->notes->all();

        for ($id = 1; $id <= static::CHILD_ENTITIES; $id++) {
            $this->assertEquals($id, $notes[$id]->id);
            $this->assertEquals("note$id", $notes[$id]->content);
        }
    }

    public function testSearch()
    {
        $organization = $this->pipedrive->organizations->find(['name' => 'organization1', 'emails' => 'email1@1.com'])->one();

        $this->assertEquals(1, $organization->id);
        $this->assertEquals('organization1', $organization->name);
        $this->assertEquals('value', $organization->field);
    }

    public function testUnsuccessfullSearch()
    {
        $person = $this->pipedrive->persons->find(['name' => 'none'])->one();

        $this->assertNull($person);
    }

    public function testSearchChained()
    {
        $notes = $this->pipedrive->organizations->find(['name' => 'organization1'])->persons->notes->all();

        for ($id = 1; $id <= static::CHILD_ENTITIES; $id++) {
            $this->assertEquals($id, $notes[$id]->id);
            $this->assertEquals("note$id", $notes[$id]->content);
        }
    }

    public function testCreate()
    {
        $id = $this->pipedrive->organizations->create(['name' => 'new organization']);

        $this->assertEquals(static::ENTITIES + 1, $id);
    }

    public function testUpdate()
    {
        $this->pipedrive->organizations->find(1)->update(['name' => 'new name']);

        $this->assertEquals('new name', $this->db['organizations'][1]->name);
    }

    protected function createDB()
    {
        $db = [];
        for ($id = 1; $id <= static::ENTITIES; $id++) {
            $organization = new \stdClass();
            $organization->id = $id;
            $organization->name = "organization$id";
            $workEmails = new \stdClass();
            $workEmails->value = "email$id@$id.com";
            $organization->emails = ['work' => $workEmails];
            $organization->hash = 'custom';
            $organization->field = 'value';
            $db['organizations'][$id] = $organization;

            $person = new \stdClass();
            $person->id = $id;
            $person->org_id = $id;
            $person->name = "person$id";
            $person->hash = 'custom';
            $db['persons'][$id] = $person;
            $db['organizations'][$id]->persons[$person->id] = $person;

            $deal = new \stdClass();
            $deal->id = $id;
            $deal->org_id = $id;
            $deal->title = "deal$id";
            $deal->hash = 'custom';
            $db['deals'][$id] = $deal;
            $db['organizations'][$id]->deals[$deal->id] = $deal;

            for ($i = 0; $i < static::CHILD_ENTITIES; $i++) {
                $note = new \stdClass();
                $note->id = (($id - 1) * static::CHILD_ENTITIES) + $i + 1;
                $note->content = "note{$note->id}";
                $note->org_id = $id;
                $note->person_id = $id;
                $db['notes'][$id] = $note;
                $db['organizations'][$id]->notes[$note->id] = $note;
                $db['persons'][$id]->notes[$note->id] = $note;
            }
        }

        return $db;
    }
}