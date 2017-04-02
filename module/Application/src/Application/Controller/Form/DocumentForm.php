<?php
// {{{ Header
/**
 *
 * @author joerg.mueller
 * @version $Id:$
 */
// }}}

namespace Application\Form;

use Zend\Form\Form;
use Model\DocumentModel;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class DocumentForm extends Form
{

	public function __construct( $name, $options=array() )
	{
		$controller = $options['c'];

		parent::__construct('\Model\DocumentModel');

// 		$this->setHydrator(new ClassMethodsHydrator(false))
// 				->setObject(new DocumentModel());


		$this->add(array(
			'name' => 'token',
			'type' => 'Zend\Form\Element\Hidden',
			'attributes' => array('id'=>'client-token')
		));

		$this->add(array(
			'name' => 'path',
			'type' => 'Zend\Form\Element\Text',
			'required' => true,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control',
				'placeholder' => $controller->translator->translate('Path'),
			)
		));

		$this->add(array(
			'name' => 'structname',
			'type' => 'Zend\Form\Element\Text',
			'required' => true,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[required]',
				'placeholder' => $controller->translator->translate('Structname'),
			)
		));

		$options = array();
		foreach($controller->config['locales']['list'] as $lang => $entry)
			$options[$lang] = $entry['short'] . ':' . $entry['name'];

		$this->add(array(
			'type' => 'Zend\Form\Element\MultiCheckbox',
			'name' => 'inlanguage',
			'options' => array(
				'label' => 'Inlanguage',
				'value_options' => $options,
				'label_attributes' => array(
					'class' => 'inline margin-right-20'
				),
			)
		));

		$this->add(array(
			'name' => 'sort',
			'type' => 'Zend\Form\Element\Number',
			'required' => true,
			'attributes' => array(
				'type' => 'number',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[required]',
				'placeholder' => $controller->translator->translate('Sort'),
			)
		));

		$this->add(array(
			'name' => 'publishedOn',
			'type' => 'Zend\Form\Element\Date',
			'required' => true,
			'attributes' => array(
				'type' => 'date',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[required]',
				'placeholder' => $controller->translator->translate('Published Start'),
				'step' => '1'
			)
		));

		$this->add(array(
			'name' => 'publishedOff',
			'type' => 'Zend\Form\Element\Date',
			'required' => false,
			'attributes' => array(
				'type' => 'date',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control',
				'placeholder' => $controller->translator->translate('Published End'),
				'step' => '1'
			),
			'options' => array(
				'format' => 'Y-m-d'
			)
		));

		$this->add(array(
			'name' => 'visible',
			'type' => 'Zend\Form\Element\Number',
			'required' => true,
			'attributes' => array(
				'type' => 'number',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[required]',
				'placeholder' => $controller->translator->translate('Visible'),
			)
		));

		$this->add(array(
				'name' => 'bgimage',
				'type' => 'Zend\Form\Element\Text',
				'required' => false,
				'attributes' => array(
					'id' => 'bgImageInput',
					'type' => 'text',
					'autocomplete' => 'off',
					'data-prompt-position' => 'topLeft',
					'class' => 'form-control',
					'placeholder' => $controller->translator->translate('Background Image'),
				)
		));

		$this->add(array(
			'name' => 'structicon',
			'type' => 'Zend\Form\Element\Text',
			'required' => false,
			'attributes' => array(
				'id' => 'structicon',
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control',
				'placeholder' => $controller->translator->translate('Struct Icon'),
			)
		));

		$this->add(array(
			'name' => 'documentclass',
			'type' => 'Zend\Form\Element\Text',
			'required' => false,
			'attributes' => array(
				'id' => 'documentclass',
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control',
				'placeholder' => $controller->translator->translate('Documentclass'),
			)
		));

		$this->add(array(
			'name' => 'georeverse',
			'type' => 'Zend\Form\Element\Text',
			'required' => true,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control',
				'placeholder' => $controller->translator->translate('Georeverse'),
			)
		));

		$this->add(array(
			'name' => 'layout',
			'type' => 'Zend\Form\Element\Textarea',
			'required' => true,
			'attributes' => array(
				'type' => 'textarea',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control h400',
				'placeholder' => $controller->translator->translate('Layout'),
			)
		));

		$this->add(array(
			'name' => 'submit',
			'attributes' => array(
				'type' => 'button',
				'id'	=> 'documentFormButton',
				'value' => $controller->translator->translate('Submit'),
				'class' => 'btn btn-primary pull-right margin-right-10',
				'id' => 'submitBtn'
			)
		));


	}
}

/**
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
