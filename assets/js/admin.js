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

  // ===== Collapsible Sections Functionality =====
  
  // Initialize collapsible sections
  function initCollapsibleSections() {
    // Get stored section states from localStorage
    var sectionStates = {};
    try {
      var stored = localStorage.getItem('lpfs_section_states');
      if (stored) {
        sectionStates = JSON.parse(stored);
      }
    } catch(e) {
      // Ignore localStorage errors
    }

    // Apply stored states
    $('.lpfs-section').each(function() {
      var sectionId = $(this).data('section-id');
      if (sectionId && sectionStates[sectionId] === 'collapsed') {
        $(this).addClass('collapsed');
      }
    });

    // Handle section header clicks
    $('.lpfs-section-header').on('click', function(e) {
      e.preventDefault();
      
      var $section = $(this).closest('.lpfs-section');
      var sectionId = $section.data('section-id');
      
      // Toggle collapsed state
      $section.toggleClass('collapsed');
      
      // Save state to localStorage
      if (sectionId) {
        try {
          var states = {};
          var stored = localStorage.getItem('lpfs_section_states');
          if (stored) {
            states = JSON.parse(stored);
          }
          
          if ($section.hasClass('collapsed')) {
            states[sectionId] = 'collapsed';
          } else {
            delete states[sectionId];
          }
          
          localStorage.setItem('lpfs_section_states', JSON.stringify(states));
        } catch(e) {
          // Ignore localStorage errors
        }
      }
    });
  }

  // Initialize sections when document is ready
  initCollapsibleSections();

  // ===== Template Selection Functionality =====
  
  // Initialize template selection
  function initTemplateSelection() {
    var selectedTemplate = null;
    
    // Handle template card clicks
    $('.lpfs-template-card').on('click', function() {
      // Remove previous selection
      $('.lpfs-template-card').removeClass('selected');
      
      // Add selection to clicked card
      $(this).addClass('selected');
      
      // Store selected template
      selectedTemplate = $(this).data('template');
    });
    
    // Handle apply template button
    $('#lpfs-apply-template').on('click', function() {
      if (!selectedTemplate) {
        alert('Please select a template first');
        return;
      }
      
      // Get template settings from data attributes
      var $selectedCard = $('.lpfs-template-card[data-template="' + selectedTemplate + '"]');
      var templateSettings = $selectedCard.data('settings');
      
      if (templateSettings) {
        applyTemplateSettings(templateSettings);
      }
    });
  }
  
  // Apply template settings to form fields
  function applyTemplateSettings(settings) {
    // Apply each setting
    $.each(settings, function(key, value) {
      var $field = $('[name$="[settings][' + key + ']"]');
      
      if ($field.length) {
        // Handle color fields
        if ($field.hasClass('lpfs-color-field')) {
          $field.val(value).trigger('change');
          // Update color picker
          $field.wpColorPicker('color', value);
        }
        // Handle select fields
        else if ($field.is('select')) {
          $field.val(value).trigger('change');
        }
        // Handle number and text fields
        else {
          $field.val(value).trigger('input');
        }
      }
    });
    
    // Show success message
    var $notice = $('<div class="notice notice-success is-dismissible"><p>Template applied successfully!</p></div>');
    $('.lpfs-templates-section').before($notice);
    
    // Auto-dismiss after 3 seconds
    setTimeout(function() {
      $notice.fadeOut(function() {
        $(this).remove();
      });
    }, 3000);
  }
  
  // Initialize on document ready
  initTemplateSelection();
});
