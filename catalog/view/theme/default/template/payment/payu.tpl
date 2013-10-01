<form action="<?php echo $action; ?>" method="post">
  <?php foreach ($fields as $name => $value) { ?>
    <?php if (is_array($value)) foreach ($value as $arr_value) { ?>
      <input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $arr_value; ?>">
    <?php } else { ?>
      <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php } ?>
  <?php } ?>

  <div class="buttons">
    <div class="right">
      <input type="submit" class="button" value="<?php echo $button_confirm; ?>">
    </div>
  </div> 
</form>
