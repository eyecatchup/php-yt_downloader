$(document).ready(function() {
  // Webkit browser CSS fix
  if ($.browser.webkit) {
    $("input[type='submit']").css("padding", "10px");
  }
/**
 *  If request parameter 'vid' is set, set as value for input field.
 */
  var urlParams = {};
  (function() {
    for(var a, b = /\+/g, c = /([^&=]+)=?([^&]*)/g, d = window.location.search.substring(1);a = c.exec(d);) {
      urlParams[unescape(a[1].replace(b, " "))] = unescape(a[2].replace(b, " "))
    }
  })();
  if ("vid" in urlParams) {
    $('#ytUrl').val(decodeURIComponent(urlParams["vid"]));
  }
/**
 *  AJAX call that submits a new download request.
 */
  function do_download(mediaOut) {

    // Get the input value.
    var ytid = $("input[name='yturl']").val();

    // Only process, if there's anything to submit.
    if(ytid !== "") {

        // Flip out the URL input bar.
        $("#download").addClass('flipOutX');

        // Write processing message to the results div.
        $("#ret").html("<img src='assets/img/yt-48.gif' id='yt-spinner' />" +
            "<p>Processing your request..</p>");

        // Send the background request.
        $.ajax({
            type:  'GET',
            url:   'ajax.dl.php',
            data:  'vid=' + ytid +
                   '&dl=' + mediaOut,
            cache: false,
            beforeSend: function() {
                // Flip in the results div with the processing message.
                window.setTimeout(function() {
                    $("#ret").removeClass("flipOutX")
                             .addClass('flipInX');
                }, 600);
            },
            success: function(ret) {
                var response = ret;

                // Flip out the results div again.
                $("#ret").removeClass("flipInX")
                         .addClass('flipOutX');

                // Delete processing message in results div.
                window.setTimeout(function() {
                    $("#ret").html("");
                }, 200);

                // Write the AJAX response into the results div.
                window.setTimeout(function() {
                    $("<img/>").attr("src","assets/img/reset.png")
                               .addClass("reset-icon")
                               .mouseover(function() {
                                   $(this).css("cursor","pointer");})
                               .mouseout(function() {
                                   $(this).css("cursor","normal");})
                               .click(function() { do_reset(); })
                               .appendTo($("#ret"));
                    $("<p>" + response + "</p>")
                        .appendTo($("#ret"));
                }, 600);

                // Flip in the results div again with the AJAX result.
                window.setTimeout(function() {
                    $("#ret").removeClass('flipOutX').addClass('flipInX');
                }, 800);
            }
        });
    }
  };

/**
 *  Start a new download.
 */
  $("#s").click(function(e) {
    e.preventDefault();

    // If there is any input, show a confirm dialog before resetting the session.
    if($("input[name='yturl']").val() !== "") {
        $.confirm({
            'title'   : 'Start new Download',
            'message' : 'Choose your download: <br />' +
              '<strong>Video</strong>: Download the video file.<br />' +
              '<strong>Audio</strong>: Convert video and download soundtrack as mp3 file.',
            'buttons' : {
              // Disabled for GitHub demo, since we can not run scripts.
              //'Audio' : {'class' : 'blue', 'action': function() { do_download("audio"); }},
              //'Video' : {'class' : 'red', 'action': function() { do_download("video"); }}
              'Audio' : {'class' : 'blue', 'action': function() { alertDemo(); }},
              'Video' : {'class' : 'red', 'action': function() { alertDemo(); }}
            }
        });
    }
    return false;
  });
  function alertDemo() {
    alert('Sorry, the download functionality is disabled for this demo version \n(since we cannot run PHP code here).');
  }

/**
 *  Public function to reset the current session.
 */
  do_reset = function() {
    // If there is any input, show a confirm dialog before resetting the session.
    if($("input[name='yturl']").val() !== "") {
        $.confirm({
            'title'   : 'You\'re about to restart!',
            'message' : 'Have you saved your download? <br />' +
              'Files cannot be restored at a later time! <br />' +
              'Continue?',
            'buttons' : {
              'Yes' : {'class' : 'blue', 'action': function() { now_really_do_the_reset(); }},
              'No'  : {'class' : 'red'}
            }
        });
    }
  };

/**
 *  Private function to reset the page
 *  after confirm dialog have been confirmed.
 */
  now_really_do_the_reset = function() {
    $("#download").addClass("flipOutX");
    $("#ret").removeClass("flipInX")
             .addClass('flipOutX');
    window.setTimeout(function() {
        $("input[name='yturl']").val("");
        $("#ret").html("");
        $("#download").removeClass("flipOutX")
                      .addClass('flipInX')
    }, 1000);
    window.setTimeout(function() {
        $("#download").removeClass('flipInX')
    }, 1300);
  };

});