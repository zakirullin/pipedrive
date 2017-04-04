# Api client for [Pipedrive](https://pipedrive.com)
## Examples
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Zakirullin\Pipedrive\Pipedrive;

$pipedrive = new Pipedrive('token');

// Get by id
$organization = $pipedrive->organizations->findOne(1);
print_r($organization);

// Get person with phone "777" for organization with name 'organization'
$person = $pipedrive->organizations->find(['name' => 'organization'])->persons->findAll(['phone' => '777']);

// Update all notes for matched organizations whole name is testsite
$notes = $pipedrive->organizations->find(['name' => 'organization'])->notes;
foreach ($notes as $note) {
    $note->content = 'Good news!';
    $this->pipedrive->notes->update($note);
}
```