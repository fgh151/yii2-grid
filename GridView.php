<?php

namespace fgh151\yii\grid;

use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\bootstrap5\ButtonDropdown;
use yii\grid\ActionColumn;
use yii\grid\SerialColumn;

class GridView extends \yii\grid\GridView
{
    public $dataColumnClass = DataColumn::class;
    public $layout = "{settings}\n{summary}\n{items}\n{pager}";

    public string $settingsButton = '<i class="fas fa-cog"></i>';

    /** @var DataColumn[] */
    public array $availableColumns = [];
    public array $defaultColumns = [];
    public array $constColumns = [
        SerialColumn::class,
        ActionColumn::class,
    ];
    private array $selectedColumns = [];
    /**
     * Разделитель между связями. Например: proposal.user.contact.email станет proposal__R__user__R__contact__R__email
     * Данная трансформация необходима тк php передает в именах POST переменных вместо точек - знак подчеркивания.
     * В свою очередь знак подчеркивания не может быть использован в чистом виде, поскольку может быть применен в snake_case нотации.
     * Использование точек возможно с директивной register_globals, которая чаще всего отключена.
     * @var string
     */
    public string $relationDelimiter = '__R__';

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

            $columns = str_replace($this->relationDelimiter, '.', array_keys($post));

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
        } else {
            $this->initColumnsAttribute('availableColumns');
        }

        if (empty($this->defaultColumns)) {
            $this->defaultColumns = $this->columns;
        } else {
            $this->initColumnsAttribute('defaultColumns');
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
        foreach ($this->availableColumns as $aColumn) {
            $type = get_class($aColumn);
            if (in_array($type, $this->constColumns)) {
                $this->columns[] = $aColumn;
            } else {
                foreach ($this->selectedColumns as $sColumn) {
                    if ($aColumn instanceof DataColumn) {
                        if ($aColumn->attribute == $sColumn) {
                            $this->columns[] = $aColumn;
                        }
                    }
                }
            }
        }
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
                $items[] = ['label' => $column->getHeaderLabel(), 'attribute' => str_replace('.', $this->relationDelimiter, $column->attribute)];
            }
        }

        $dropdownId = uniqid('dropdown');

        return ButtonDropdown::widget([
            'encodeLabel' => false,
            'label' => $this->settingsButton,
            'dropdownClass' => SettingsDropdown::class,
            'dropdown' => [
                'relationDelimiter' => $this->relationDelimiter,
                'formId' => $dropdownId,
                'items' => $items,
                'modelClass' => $this->dataProvider->query->modelClass,
                'selectedItems' => $this->selectedColumns,
            ],
        ]);
    }
}