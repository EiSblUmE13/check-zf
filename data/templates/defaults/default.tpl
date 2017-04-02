
<div class="row-fluid">
	<div class="col-md-12 padding-0">
		HeadSlider
	</div>
</div>

<div class="row-fluid wrapper">

	<div class="col-md-12">

		<div class="widgetHolder"  data-path="<?=$document->getId()?>/col-md-12/1" data-valid="teaser|article|article_list">
			<?php
			$widgets = $dm->createQueryBuilder('Model\WidgetModel')
						->field('parent')->references($document)
						->field('anker')->equals($document->getId()."/col-md-12/1")
						->sort('datecreate', 'asc')
						->getQuery()->execute();
			foreach($widgets as $widget) {
				$widget = $widget->toArray();
				include WIDGET . DIRECTORY_SEPARATOR . str_replace('_','/',$widget['type']).'.phtml';
			}
			?>
		</div>

	</div>

</div>