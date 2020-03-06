$(function() {
    $('input').keyup(function() {
        this.value = this.value.toLocaleUpperCase();
    });
});