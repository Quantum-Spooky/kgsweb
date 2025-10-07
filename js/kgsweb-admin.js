(function($){

  window.kgsweb = window.kgsweb || {};

  /**
   * Extract Google Drive folder ID from full URL or return input as-is
   */
  function extractDriveFolderId(input) {
    const match = input.trim().match(/\/folders\/([a-zA-Z0-9-_]+)/);
    return match ? match[1] : input;
  }

 



  $(document).ready(function(){


  });

})(jQuery);
