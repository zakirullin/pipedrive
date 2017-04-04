# PHP API client for [Pipedrive](https://pipedrive.com)
## Examples
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Zakirullin\Pipedrive\Pipedrive;

$pipedrive = new Pipedrive('token');

// From simple get by id
$organization = $pipedrive->users->findOne(1);
print_r($organization);

// To little bit more powerfull get Vasya person for organization with name 'testsite.com'
$person = $pipedrive->organizations->find(['name' => 'testsite'])->persons->findAll(['phone' => '777']);

//And here where POWER comes - update all notes for matched organizations whole name is testsite
$notes = $pipedrive->organizations->find(['name' => 'testsite'])->notes;
foreach ($notes as $note) {
    $note->content = 'Good news!';
    $this->pipedrive->notes->update($note);
}
```