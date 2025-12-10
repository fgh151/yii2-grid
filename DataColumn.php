<?php

namespace fgh151\yii\grid;

class DataColumn extends \yii\grid\DataColumn
{
    public function getHeaderLabel(): ?string
    {
        return $this->getHeaderCellLabel();
    }
}