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
      // Handle font family initialization
      if (name && name.includes('font-family')) {
        if (val) {
          loadGoogleFont(val);
          setVar(name, "'" + val + "', sans-serif");
        } else {
          setVar(name, 'inherit');
        }
      } else {
        setVar(name, val);
      }
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

  // when a select field changes (for font weight and font family)
  $('.lpfs-select-field').on('change', function(){
    var $f    = $(this),
        name  = $f.data('var'),
        val   = $f.val();
    
    // Handle font family changes
    if (name.includes('font-family')) {
      if (val) {
        // Load Google Font for preview
        loadGoogleFont(val);
        setVar(name, "'" + val + "', sans-serif");
      } else {
        setVar(name, 'inherit');
      }
    } else {
      setVar(name, val);
    }
  });

  // Function to load Google Fonts dynamically for preview
  function loadGoogleFont(fontName) {
    if (!fontName) return;
    
    // Check if font is already loaded
    var fontId = 'lpfs-font-' + fontName.replace(/\s+/g, '-').toLowerCase();
    if ($('#' + fontId).length) return;
    
    // Create Google Fonts URL with proper format
    var fontUrl = 'https://fonts.googleapis.com/css2?family=' + 
                  fontName.replace(/\s+/g, '+') + ':wght@400;500;600;700&display=swap';
    
    // Add font link to head
    $('<link>')
      .attr('id', fontId)
      .attr('rel', 'stylesheet')
      .attr('href', fontUrl)
      .appendTo('head');
    
    // Wait for font to load before applying
    setTimeout(function() {
      // Force a repaint to ensure font is applied
      $('#lpfs-preview').hide().show(0);
    }, 100);
  }
});
