<?php
$this->breadcrumbs=array(
	'Members'=>array('index'),
	$model->name,
);

$this->menu=array(
	array('label'=>'List Member','url'=>array('index')),
	array('label'=>'Create Member','url'=>array('create')),
	array('label'=>'Update Member','url'=>array('update','id'=>$model->id)),
	array('label'=>'Delete Member','url'=>'#','linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage Member','url'=>array('admin')),
);
?>

<h1>View Member #<?php echo $model->id; ?></h1>

<?php $this->widget('bootstrap.widgets.TbDetailView',array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'address',
		'name',
		'vars',
		'status',
		'created_at',
		'modified_at',
	),
)); ?>
