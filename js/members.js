jQuery(document).ready(function($){
   // Enable multicomplete on selected inputs
   $('.MultiComplete').livequery(function() {
      $(this).autocomplete(
         gdn.url('/dashboard/user/autocomplete/'),
         {
            minChars: 1,
            multiple: true,
            scrollHeight: 220,
            selectFirst: true
         }
      ).autogrow();
   });
});
