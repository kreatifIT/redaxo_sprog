<?php

/**
 * This file is part of the Sprog package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Sprog\Wildcard;

$content = '';
$message = '';

// -------------- Defaults
$pid = rex_request('pid', 'int');
$wildcard_id = rex_request('wildcard_id', 'int');
$func = rex_request('func', 'string');
$clang_id = (int)str_replace('clang', '', rex_be_controller::getCurrentPagePart(3));


$error = '';
$success = '';

// ----- delete wildcard
if ($func == 'delete' && $wildcard_id > 0) {
    $deleteWildcard = rex_sql::factory();
    $deleteWildcard->setQuery('DELETE FROM ' . rex::getTable('sprog_wildcard') . ' WHERE id=?', [$wildcard_id]);
    $success = $this->i18n('wildcard_deleted');

    $func = '';
    unset($wildcard_id);
}

if ($func == '') {
    $searchTerm = rex_request('search-term', 'string');
    $title = $this->i18n('wildcard_caption');
    
    if (strlen($searchTerm))
    {
        $search = " AND (`wildcard` LIKE '%". $searchTerm ."%' OR `replace` LIKE '%". $searchTerm ."%')";
    }

    $list = rex_list::factory('SELECT `pid`, `id`, `wildcard`, `replace` FROM ' . rex::getTable('sprog_wildcard') . ' WHERE `clang_id`="' . $clang_id . '" '. $search .' ORDER BY wildcard');
    $list->addTableAttribute('class', 'table-striped');
    
    if (strlen($searchTerm)) {
        $list->addParam('search-term', $searchTerm);
    }

    $tdIcon = '<i class="rex-icon rex-icon-refresh"></i>';
    $thIcon = rex::getUser()->getComplexPerm('clang')->hasAll() ? '<a href="' . $list->getUrl(['func' => 'add']) . '#wildcard"' . rex::getAccesskey($this->i18n('add'), 'add') . '><i class="rex-icon rex-icon-add-article"></i></a>' : '';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'pid' => '###pid###']);

    $list->removeColumn('pid');

    $list->setColumnLabel('id', $this->i18n('id'));
    $list->setColumnLayout('id', ['<th class="rex-table-id">###VALUE###</th>', '<td class="rex-table-id">###VALUE###</td>']);

    $list->setColumnLabel('wildcard', $this->i18n('wildcard'));
    $list->setColumnLabel('replace', $this->i18n('wildcard_replace'));

    $list->addColumn('edit', '<i class="rex-icon rex-icon-edit"></i> ' . $this->i18n('edit'));
    $list->setColumnLabel('edit', $this->i18n('function'));
    $list->setColumnLayout('edit', ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'pid' => '###pid###']);

    $list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> ' . $this->i18n('delete'));
    $list->setColumnLabel('delete', $this->i18n('function'));
    $list->setColumnLayout('delete', ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('delete', ['func' => 'delete', 'wildcard_id' => '###id###']);
    $list->addLinkAttribute('delete', 'data-confirm', $this->i18n('delete') . ' ?');

    $formElements = '
        <div class="panel-body form-group">
            <label for="exampleInputName2">'. $this->i18n('wildcard_search_term') .'</label>
            <input type="text" class="form-control text-right" name="search-term" value="'. $searchTerm .'"/>
            <button type="submit" class="btn btn-primary">'. $this->i18n('search') .'</button>
        </div>';
    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('wildcard_search'));
    $fragment->setVar('content', $formElements, false);
    $content = '<form action="' . \rex_url::currentBackendPage() . '" method="post" class="form-inline">'. $fragment->parse('core/page/section.php') .'</form>';
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', $title);
    $fragment->setVar('content', $list->get(), false);
    $content .= $fragment->parse('core/page/section.php');
} else {
    $title = $func == 'edit' ? $this->i18n('edit') : $this->i18n('add');

    \rex_extension::register('REX_FORM_CONTROL_FIELDS', '\Sprog\Extension::wildcardFormControlElement');

    $form = rex_form::factory(rex::getTable('sprog_wildcard'), '', 'pid = ' . $pid);
    $form->addParam('pid', $pid);
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setLanguageSupport('id', 'clang_id');
    $form->setEditMode($func == 'edit');

    if ($func == 'add' && rex::getUser()->getComplexPerm('clang')->hasAll()) {
        $field = $form->addTextField('wildcard', rex_request('wildcard_name', 'string', null));
        $field->setNotice($this->i18n('wildcard_without_tag'));
    } else {
        $field = $form->addReadOnlyField('wildcard', rex_request('wildcard_name', 'string', null));
    }
    $field->setLabel($this->i18n('wildcard'));
    $field->getValidator()->add('notEmpty', $this->i18n('wildcard_error_no_wildcard'));

    $field = $form->addTextAreaField('replace');
    $field->setLabel($this->i18n('wildcard_replace'));

    $content .= $form->get();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $title);
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');
}

echo $message;
echo $content;

if (rex::getUser()->getComplexPerm('clang')->hasAll()) {
    echo Wildcard::getMissingWildcardsAsTable();
}
