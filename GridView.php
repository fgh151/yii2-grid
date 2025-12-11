<?php

namespace fgh151\yii\grid;

use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\bootstrap5\ButtonDropdown;
use yii\web\JsExpression;

class GridView extends \yii\grid\GridView
{
    public $dataColumnClass = DataColumn::class;
    public $layout = "{settings}\n{summary}\n{items}\n{pager}";

    public string $settingsButton = '<i class="fas fa-cog"></i>';

    /** @var DataColumn[] */
    public array $availableColumns = [];
    private array $selectedColumns = [];

    public array $defaultColumns = [];

    /**
     * @noinspection PhpMissingReturnTypeInspection
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (Yii::$app->getRequest()->post('gridSettings')) {
            $post = Yii::$app->getRequest()->post();

            unset($post['gridSettings']);
            unset($post[Yii::$app->getRequest()->csrfParam]);

            $columns = array_keys($post);

            Yii::$app->session->set('selectedColumns', $columns);
        }

        /** @noinspection CssUnusedSymbol */
        $this->view->registerCss(<<<CSS
.grid-view .summary {
display: inline-flex;
}

CSS
        );
        if (empty($this->availableColumns)) {
            $this->availableColumns = $this->columns;
        }
        if (empty($this->defaultColumns)) {
            $this->defaultColumns = $this->columns;
        }

        $this->selectedColumns = Yii::$app->session->get('selectedColumns', []);

        if (empty($this->selectedColumns)) {
            foreach ($this->availableColumns as $column) {
                if ($column instanceof DataColumn) {
                    $this->selectedColumns[] = $column->attribute;
                }
            }
        }

        $this->columns = [];

        foreach ($this->selectedColumns as $sColumn) {

            foreach ($this->availableColumns as $aColumn) {
                if (is_string($aColumn) && str_starts_with(str_replace('.', '_', $aColumn), $sColumn)) {
                    $this->columns[] = $aColumn;
                } elseif  ($aColumn == $sColumn) {
                    $this->columns[] = $aColumn;
                } elseif (is_array($aColumn) && isset($aColumn['attribute']) && $aColumn['attribute'] == $sColumn) {
                    $this->columns[] = $aColumn;
                }
            }
        }

        $this->initColumns();

        $this->initColumnsAttribute('defaultColumns');
        $this->initColumnsAttribute('availableColumns');
    }

    /**
     * @throws InvalidConfigException
     */
    protected function initColumnsAttribute(string $attribute): void
    {
        if (empty($this->$attribute)) {
            $this->guessColumns();
        }
        foreach ($this->$attribute as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge([
                    'class' => $this->dataColumnClass,
                    'grid' => $this,
                ], $column));
            }
            if (!$column->visible) {
                unset($this->$attribute[$i]);
                continue;
            }
            $this->$attribute[$i] = $column;
        }
    }

    /**
     * Renders a section of the specified name.
     * If the named section is not supported, false will be returned.
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     * @return string|bool the rendering result of the section, or false if the named section is not supported.
     * @throws Throwable
     */
    public function renderSection($name): bool|string
    {
        return match ($name) {
            '{summary}' => $this->renderSummary(),
            '{items}' => $this->renderItems(),
            '{pager}' => $this->renderPager(),
            '{sorter}' => $this->renderSorter(),
            '{settings}' => $this->renderSettings(),
            default => false,
        };
    }

    /**
     * @throws Throwable
     */
    public function renderSettings(): string
    {
        $items = [
            ['label' => 'Колонки для отображения', 'options' => ['class' => 'dropdown-header']],
        ];

        foreach ($this->availableColumns as $column) {
            if ($column instanceof DataColumn) {
                $items[] = ['label' => $column->getHeaderLabel(), 'attribute' => $column->attribute];
            }
        }

        $dropdownId = uniqid('dropdown');

        return ButtonDropdown::widget([
            'encodeLabel' => false,
            'label' => $this->settingsButton,
            'dropdownClass' => SettingsDropdown::class,
            'dropdown' => [
                'formId' => $dropdownId,
                'items' => $items,
                'modelClass' => $this->dataProvider->query->modelClass,
                'selectedItems' => $this->selectedColumns,
            ],
        ]);
    }
}