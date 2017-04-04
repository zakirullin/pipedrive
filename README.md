# API client for [Pipedrive](https://pipedrive.com)
# Can be installed via Composer [package](https://packagist.org/packages/zakirullin/pipedrive)
## Examples
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Zakirullin\Pipedrive\Pipedrive;

$pipedrive = new Pipedrive('token');

// Get by id
$organization = $pipedrive->organizations->findOne(1);

// Create
$id = $pipedrive->notes->create(['content' => 'Note']);

// Update
$pipedrive->persons->find(1)->update(['name' => 'New name']);

// Find person with phone '777' for organization with name 'Github'
$person = $pipedrive->organizations->find(['name' => 'Github'])->persons->findAll(['phone' => '777']);

// Update all notes for matched organizations whole name is 'Github'
$notes = $pipedrive->organizations->find(['name' => 'Github'])->notes;
foreach ($notes as $note) {
    $note->content = 'Good news!';
    $this->pipedrive->notes->update($note);
}
```
# API Docs can be found [here](https://developers.pipedrive.com/v1)
