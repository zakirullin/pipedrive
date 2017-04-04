# API client for [Pipedrive](https://pipedrive.com)
## Examples
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Zakirullin\Pipedrive\Pipedrive;

$pipedrive = new Pipedrive('token');

// Get by id
$organization = $pipedrive->organizations->findOne(1);
print_r($organization);

// Create
$id = $pipedrive->notes->create(['content' => 'Note']);

// Update
$pipedrive->persons->find(1)->update(['name' => 'New name']);

// Find person with phone "777" for organization with name 'organization'
$person = $pipedrive->organizations->find(['name' => 'Organization'])->persons->findAll(['phone' => '777']);

// Update all notes for matched organizations whole name is testsite
$notes = $pipedrive->organizations->find(['name' => 'Organization'])->notes;
foreach ($notes as $note) {
    $note->content = 'Good news!';
    $this->pipedrive->notes->update($note);
}
```
# API Docs can be found [here](https://developers.pipedrive.com/v1)
