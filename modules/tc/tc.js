$(function() {
	$('input#start_session').datetimepicker({
        format: 'dd-mm-yyyy hh:ii',
        pickerPosition: 'bottom-right',
        //language: '" . $language . "',
        autoclose: true
    });
    $('#BBBEndDate').datetimepicker({
        format: 'dd-mm-yyyy hh:ii',
        pickerPosition: 'bottom-right',
        //language: '" . $language . "',
        autoclose: true
    }).on('changeDate', function(ev){
        if($(this).attr('id') === 'BBBEndDate') {
            $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
        }
    }).on('blur', function(ev){
        if($(this).attr('id') === 'BBBEndDate') {
            var end_date = $(this).val();
            if (end_date === '') {
                if ($('input[name="dispresults"]:checked').val() == 4) {
                    $('input[name="dispresults"][value="1"]').prop('checked', true);
                }
                $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
            }
        }
    });
    $('#enableEndDate').change(function() {
        var dateType = $(this).prop('id').replace('enable', '');
        if($(this).prop('checked')) {
            $('input#BBB'+dateType).prop('disabled', false);
            if (dateType === 'EndDate' && $('input#BBBEndDate').val() !== '') {
                $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
            }
        } else {
            $('input#BBB'+dateType).prop('disabled', true);
            if ($('input[name="dispresults"]:checked').val() == 4) {
                $('input[name="dispresults"][value="1"]').prop('checked', true);
            }
            $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
        }
    });
    $('#tags_1').select2({tags:[], formatNoMatches: ''});
});

$(document).ready(function () {
    $('#popupattendance1').click(function() {
    window.open($(this).prop('href'), '', 'height=200,width=500,scrollbars=no,status=no');
    return false;
    });

    $('#select-groups').select2();
    $('#selectAll').click(function(e) {
        e.preventDefault();
        var stringVal = [];
        $('#select-groups').find('option').each(function(){
            stringVal.push($(this).val());
        });
        $('#select-groups').val(stringVal).trigger('change');
    });
    $('#removeAll').click(function(e) {
        e.preventDefault();
        var stringVal = [];
        $('#select-groups').val(stringVal).trigger('change');
    });
});


