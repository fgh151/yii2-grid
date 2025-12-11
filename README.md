Grid view
=========
Grid view with swithcable columns

Installation
------------

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fgh151/yii2-grid "*"
```

or add

```
"fgh151/yii2-grid": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
use fgh151\yii\grid\GridView;

/**
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $searchModel \yii\db\ActiveRecord
 */

$availableColumns ['id', 'email', 'firstname', 'lastname'];
$defaultColumns = ['id', 'email'];

echo GridView::widget([
        'dataProvider' => $dataProvider, // required
        'filterModel' => $searchModel, // default is null
        'defaultColumns' => $defaultColumns, // default is [], if you want to use default columns
        'availableColumns' => $availableColumns, // default is [], if you want to use default columns
]);
```
