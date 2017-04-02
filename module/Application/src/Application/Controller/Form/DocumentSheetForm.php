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
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

use Model\DocumentSheetModel;

class DocumentSheetForm extends Form
{

	public function __construct( $name, $options=array() )
	{
		$controller = $options['c'];

		parent::__construct('\Model\DocumentSheetModel');

// 		$this->setHydrator(new ClassMethodsHydrator(false))
// 				->setObject(new DocumentSheetModel());



		$this->add(array(
			'name' => 'title',
			'type' => 'Zend\Form\Element\Text',
			'required' => true,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[required]',
				'placeholder' => $controller->translator->translate('Title'),
			)
		));

		$this->add(array(
			'name' => 'description',
			'type' => 'Zend\Form\Element\Text',
			'required' => false,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[]',
				'placeholder' => $controller->translator->translate('Description'),
			)
		));

		$this->add(array(
			'name' => 'keywords',
			'required' => false,
			'type' => 'Zend\Form\Element\Text',
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[]',
				'placeholder' => $controller->translator->translate('Keywords'),
			)
		));

		$this->add(array(
			'name' => 'indexfollow',
			'type' => 'Zend\Form\Element\Text',
			'required' => false,
			'attributes' => array(
				'type' => 'text',
				'autocomplete' => 'off',
				'data-prompt-position' => 'topLeft',
				'class' => 'form-control validate[]',
				'placeholder' => $controller->translator->translate('Keywords'),
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
