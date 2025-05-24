jQuery(function($){
  // helper to set a CSS var on the preview
  function setVar(name, value) {
    $('#lpfs-preview').get(0).style.setProperty('--' + name, value);
  }

  // initialize from saved controls
  $('.lpfs-color-field, .lpfs-number-field, .lpfs-select-field').each(function(){
    var $field = $(this),
        name   = $field.data('var'),    // see step 3
        unit   = $field.data('unit') || '',
        val    = $field.is('.lpfs-number-field')
                 ? $field.val() + unit
                 : $field.val();

    if ( val ) {
      setVar(name, val);
    }
  });

  // when a color picker changes
  $('.lpfs-color-field').wpColorPicker({
    change: function(event, ui) {
      var name = $(this).data('var'),
          val  = ui.color.toString();
      setVar(name, val);
    },
    clear: function() {
      var name = $(this).data('var');
      setVar(name, ''); // resets to CSS default
    }
  });

  // when a number input changes
  $('.lpfs-number-field').on('input', function(){
    var $f    = $(this),
        name  = $f.data('var'),
        unit  = $f.data('unit') || '',
        val   = $f.val() + unit;
    setVar(name, val);
  });

  // when a select field changes (for font weight)
  $('.lpfs-select-field').on('change', function(){
    var $f    = $(this),
        name  = $f.data('var'),
        val   = $f.val();
    setVar(name, val);
  });
});
