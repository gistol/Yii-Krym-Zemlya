<?php
$this->breadcrumbs=array(
	Yii::t('common', 'Mailing messages'),
);

$this->adminTitle = tt('History messages with user', 'messages'). ' "'.$senderInfo->username.'"';
?>

<div class="form">
	<?php $this->renderPartial('//../modules/messages/views/backend/__form_message', array('model' => $model, 'uid' => $uid));?>
</div>

<div class="box_message">
	<?php if ($allMessages) : ?>
		<?php foreach($allMessages as $item) : ?>
			<?php
			$addClass = '';
			if ($item->id_userFrom == Yii::app()->user->id)
				$addClass = 'i-message';
			else
				$addClass = 'other-message';
			?>
			<div class="message_contact_read <?php echo $addClass; ?>">
				<div class="message_contact_message">
					<h3 class="author">
						<?php if ($item->id_userFrom == Yii::app()->user->id): ?>
							<?php echo tt('I am', 'messages');?>
						<?php else: ?>
							<?php echo CHtml::encode($item->userInfoFrom->username);?>
						<?php endif; ?>
					</h3>
					<span class="message_contact_date">
						<?php echo $item->date_send;?>
					</span>

					<blockquote><?php echo Messages::messageFormat($item);?></blockquote>
				</div>

				<?php if (isset($item->messagesFiles) && $item->messagesFiles) : ?>
					<div class="message_contact_message">
						<p class="files"><strong><?php echo tt('Files', 'messages');?></strong>:</p>
						<p><?php echo Messages::getFiles($item);?></p>
					</div>
				<?php endif;?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>

<?php if (isset($pages) && $pages->pageCount > 1) : ?>
	<div class="clear"></div>
	<div class="pagination">
		<?php $this->widget('bootstrap.widgets.TbPager',array('pages' => $pages, 'header' => '')); ?>
	</div>
	<div class="clear"></div>
<?php endif; ?>
