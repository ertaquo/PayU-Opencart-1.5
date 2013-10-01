<form action="<?php echo $action; ?>" method="post" id="payment_form">
  <?php foreach ($fields as $name => $value) { ?>
    <?php if (is_array($value)) foreach ($value as $arr_value) { ?>
      <input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $arr_value; ?>">
    <?php } else { ?>
      <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php } ?>
  <?php } ?>

  <div class="buttons">
    <div class="right">
      <input type="button" id="payment" class="button" value="<?php echo $button_confirm; ?>">
    </div>
  </div> 
</form>

<script type="text/javascript">
$(document).ready(function(){
  $('#payment').click(function(){
    $.ajax({
      type: 'GET',
      url: 'index.php?route=payment/payu/confirm',
      success: function(){
        $('#payment_form').submit();
      }
    });

    return false;
  });
});
</script>
