<?php

namespace fgh151\yii\grid;

use yii\helpers\StringHelper;
use yii\helpers\Html;

class DataColumn extends \yii\grid\DataColumn
{
    /**
     * @inheritdoc
     */
    public $encodeLabel = false;

    public int $trimLabelLength = 10;

    public function getHeaderLabel($trim = false): ?string
    {
        if ($trim) {
            return $this->getHeaderCellLabel();
        }
        return parent::getHeaderCellLabel();
    }

    protected function getHeaderCellLabel()
    {
        $full = parent::getHeaderCellLabel();
        $short = StringHelper::truncate($full, $this->trimLabelLength);

        return Html::tag('span', $short, ['title' => $full, 'data-bs-toggle'=>"tooltip"]);
    }
}