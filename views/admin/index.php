<!--<h3>Import a tumblr blog</h3>-->

<p>This script will use your tumblr blog XML feed to import posts, pages and tags into PyroCMS.</p>

<p><strong>Note:</strong> Video posts will not be imported.</p>

<?php echo form_open(uri_string(), 'class="crud"'); ?>
	<ul>
		<li class="<?php echo alternator('', 'even'); ?>">
			<?php echo form_label('Tumblr blog URL', 'blog_url'); ?>
			<input type="text" id="blog_url" name="blog_url" maxlength="255" value="<?php echo $data->blog_url; ?>" />
			<span class="required-icon tooltip"><?php echo lang('required_label'); ?></span>
			<br/>
			<small><em>(eg: http://myblog.tumblr.com)</em></small>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<label for="posts">
				Import posts
			</label>
			<?php echo form_dropdown('posts', array(1 => 'Yes', 0 => 'No'), $data->posts) ?>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<label for="pages">
				Import pages
			</label>
			<?php echo form_dropdown('pages', array(1 => 'Yes', 0 => 'No'), $data->pages) ?>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<label for="categories">
				Import tags as cateogories
			</label>
			<?php echo form_dropdown('categories', array(1 => 'Yes', 0 => 'No'), $data->categories) ?>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<label for="status">Post/page publish status</label>
			<?php echo form_dropdown('status', array('draft' => lang('blog_draft_label'), 'live' => lang('blog_live_label')), $data->status) ?>
		</li>
		<li class="<?php echo alternator('', 'even'); ?>">
			<label for="redirects">
				Add redirects
			</label>
			<?php echo form_dropdown('redirects', array(1 => 'Yes', 0 => 'No'), $data->redirects) ?>
		</li>	
	</ul>
	<div class="buttons float-right padding-top">
		<button class="button" value="save" name="btnAction" type="submit">
			<span>Import</span>
		</button>
	</div>
<?php echo form_close(); ?>
