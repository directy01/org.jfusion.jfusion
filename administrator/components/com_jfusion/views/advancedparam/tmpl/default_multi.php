<?php

/**
 * This is view file for advancedparam
 *
 * PHP version 5
 *
 * @category   JFusion
 * @package    ViewsAdmin
 * @subpackage Advancedparam
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

$uri = JURI::getInstance();
$uri->delVar('task');
?>
<div class="jfusion">
	<h1>Select Plugin Multi</h1>

	<form action="<?php echo $uri->toString() ?>" method="post" name="adminForm" id="adminForm" class="form-horizontal">
		<?php
		if (isset($this->error)) {
			echo $this->error;
		}
		?>
		<table class="paramlist jfusiontable" style="width:100%;border-spacing:1px;">
			<tbody>
			<tr>
				<td class="paramlist_key">JFusion Plugin</td>
				<td class="paramlist_value"><?php echo $this->output; ?></td>
			</tr>
			<tr style="padding:0; margin:0;">
				<td colspan="2" style="padding:0; margin:0;">
					<?php
					global $jname;
					echo JHtml::_('tabs.start','tabs', array('startOffset'=>2));
					foreach ($this->comp as $key => $value) {
						$jname = $key;
						echo JHtml::_('tabs.panel',JText::_($jname), $jname.'_jform_fieldset_label');

						echo '<div align="right"><input type="button" name="remove" value="Remove" onclick="JFusion.removePlugin(this, \'' . $key . '\');" style="margin-left: 3px;" /></div>';
						echo '<fieldset class="jfusionform">';
						if (isset($value['form'])) {
							$form = $value['form'];
							$fieldsets = $form->getFieldsets();
							foreach ($fieldsets as $fieldset):
								echo '<fieldset class="panelform">';
								$fields = $form->getFieldset($fieldset->name);
								foreach($fields as $field):
									// If the field is hidden, just display the input.
									echo '<div class="control-group">';
										if (!$field->hidden):
											echo '<div class="control-label">';
												echo $field->label;
											echo '</div>';
										endif;
										echo '<div class="controls">';
											echo $field->input;
										echo '</div>';
									echo '</div>';
								endforeach;
								echo '</fieldset>';
							endforeach;
						}
						echo '<input type="hidden" name="params[' . $key . '][jfusionplugin]" value="' . $value['jfusionplugin'] . '" />';
						echo '</fieldset>';
					}
					echo JHtml::_('tabs.end');
					?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div align="right">
						<input type="submit" value="Save" />
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		<input type="hidden" name="task" value="advancedparamsubmit" />
		<input type="hidden" name="jfusion_task" value="" />
		<input type="hidden" name="jfusion_value" value="" />
	</form>
</div>