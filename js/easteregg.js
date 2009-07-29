
var therightone = '';
var yourscore = 0;
var firsttime = 1;

function init() {

    // If it's not a mozilla browser, bail, 'cause I'm using some mozilla-only code
    if (!$.browser.mozilla) {
        return;
    }

    $('#egglaunch').bind('click', launchEgg);

    $('#firstchoice').bind('click', {name: 'first'}, chooseName);
    $('#secondchoice').bind('click', {name: 'second'}, chooseName);
    $('#thirdchoice').bind('click', {name: 'third'}, chooseName);
    $('#fourthchoice').bind('click', {name: 'fourth'}, chooseName);
}

function launchEgg() {
    $('#easteregg').toggle('slow');
    if (firsttime == 1) {
        getSomeData();
        setTimeout("$('#message').fadeOut('slow')", 15000);
        firsttime = 0;
    }
}


function getSomeData() {
    $.getJSON("https://ldap.mozilla.org/phonebook/egg.php", function (data) {

        // The right answer
        therightone = data.response.therightone;

        // Our new mystery person
        $('#mystery').attr('src', 'https://ldap.mozilla.org/phonebook/pic.php?mail=' + data.response.picture);

        // Fill in the names
        $.each(data.response.names, function(i,item) {
            $.each(item, function(i,more) {
                $('#' + i).show();
                $('#' + i).text(more);
            });
        });
    });

}

function chooseName(choice) {
    if (choice.data.name == therightone) {
        updateScore();
        getSomeData();
    } else {
        $('#' + choice.data.name + 'choice').fadeOut('slow');
    }
}

function updateScore() {
    yourscore = yourscore + 1;
    
    if (yourscore == 1) {
        $('#score').text('You know 1 person!');
    } else {
        $('#score').text('You know ' + yourscore + ' people!');
    }
}
