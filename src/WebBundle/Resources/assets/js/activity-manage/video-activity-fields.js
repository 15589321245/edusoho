jQuery.validator.addMethod("unsigned_integer", function (value, element) {
    return this.optional(element) || /^([1-9]\d*|0)$/.test(value);
}, "时长必须为非负整数");

jQuery.validator.addMethod("second_range", function (value, element) {
    return this.optional(element) || /^([0-9]|[012345][0-9]|59)$/.test(value);
}, "秒数只能在0-59之间");

function _inItStep2form() {
    var $step1_form = $('#step2-form');
    var validator = $step1_form.data('validator', validator);
    validator = $step1_form.validate({
        onkeyup: false,
        ignore: "",
        rules: {
            content: 'required',
            minute: 'required unsigned_integer',
            second: 'second_range',
            media: 'required'
        },
        messages: {
            content: "请输入简介",
            minute: {
                required: '请输入时长',
                unsigned_integer: '时长必须为非负整数',
            },
            second: {
                unsigned_integer: '时长必须为非负整数',
            },
            media: "请选择或者上传视频"
        }
    });
    
}

_inItStep2form();