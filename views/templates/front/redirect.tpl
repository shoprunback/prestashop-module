<script type="text/javascript">
    var redirectURL = '{$redirectURL nofilter}';
    var url = '{$url nofilter}';

    if (redirectURL) {
        window.open(redirectURL, '_blank');
    }

    (function() {
        window.location.href = url;
    })();
</script>
