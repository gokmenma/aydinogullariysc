<script>
    document.getElementById("submitButton").addEventListener("click",function(){
		var form=document.getElementById("myForm");
    form.submit();
	})

    $(document).ready(function(){
        window.setTimeout(function () {
            $("#myAlert").fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();

            });
        }, 3000);
       
       
    });
</script>
