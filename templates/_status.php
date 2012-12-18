<div>
  <select name="org_code">
  <?php
  foreach (array_keys($orgs) as $org_name) { 
    $selected = ($org_name == $current_org) ? ' selected="selected"' : '';
    echo "<option value=\"$org_name\"$selected>$orgs[$org_name]</option>";
  }
  ?>
  </select>
</div>

<div>
  <select name="employee_type_code">
  <?php
  foreach (array_keys($emp_type) as $emp_type_name) {
    $selected = ($emp_type_name == $current_emp_type) ? ' selected="selected"' : '';
    echo "<option value=\"$emp_type_name\"$selected>{$emp_type[$emp_type_name]}</option>";
  }
  ?>
  </select>
</div>

<div>
<input type="checkbox" name="is_manager" value="<?= $is_manager; ?>"<?= ($is_manager == 1) ? ' checked="checked"' : '' ?> /> User is a manager
</div>
