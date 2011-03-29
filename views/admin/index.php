
<p>
	<em>
		<strong>Note:</strong> Only the latest 50 posts will be imported. Pages will not be imported.
	</em>
</p>

<?php echo form_open(uri_string(), 'class="crud"'); ?>
	<ul>
		<li class="<?php echo alternator('', 'even'); ?>">
			<?php echo form_label('Tumblr blog URL', 'blog_url'); ?>
			<?php echo form_input('blog_url', $data->blog_url); ?>
			<span class="required-icon tooltip"><?php echo lang('required_label'); ?></span>
			<br/>
			<small><em>(eg: http://myblog.tumblr.com)</em></small>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<?php echo form_label('Import tags', 'categories'); ?>
			<?php echo form_dropdown('categories', array('1' => 'Yes', '0' => 'No'), $data->categories) ?>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<?php echo form_label('Publish status', 'status'); ?>
			<?php echo form_dropdown('status', array('draft' => lang('blog_draft_label'), 'live' => lang('blog_live_label')), $data->status) ?>
		</li>
	</ul>
	<div class="buttons float-right padding-top">
		<button class="button" value="save" name="btnAction" type="submit">
			<span>Import</span>
		</button>
	</div>
<?php echo form_close(); ?>
