
// ========================

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create','UA-26837634-1','auto');
ga('send','pageview');

// ========================

function populate_table_with_posts ( url, table_id, table_time_id ) {
  $.getJSON(url).done(
    function(data){$(table_id).dataTable({
     data: data,
     destroy: true,
     paging: false,
     searching: false,
     ordering: false,
     info: false,
     autoWidth: false,
     fnRowCallback: function ( nRow, aData, iDisplayIndex, iDisplayIndexFull ) { if ( aData.compare == 1 ) { $(nRow).addClass('success'); } },
     columns: [{"data":"rank"},{"data":"title"},{"data":"points"}],
     columnDefs: [{
       targets: 1,
       render: function ( data, type, row, meta ) { return('<a href="'+row.url+'">'+data+'</a>'); }},{
       targets: 2,
       render: function ( data, type, row, meta ) { return('<a href="https://news.ycombinator.com/item?id='+row.postid+'">'+data+'</a>'); }
     }]});
     $(table_time_id).text(new Date(data[0].etime*1000).toString());
  });
}

// ========================

function populate_graph_with_lines ( url, graph_id, click_callback ) {
  var days = ['Sun','Mon','Tue','Wed','Thur','Fri','Sat'];
  $.getJSON(url).done(function(data){
      var fields = Object.keys(data[0]);
      fields.splice(fields.indexOf('etime'),1);
      fields.splice(fields.indexOf('offset'),1);
      new Morris.Line({
        resize: true,
        hideHover: true,
        element: graph_id,
        data: data,
        xkey: 'etime',
        ykeys: fields,
        labels: fields,
        dateFormat: function (x) { return new Date(x*1000).toString(); },
        xLabelFormat: function (x) { var d = new Date(x*1000); return days[d.getDay()]+' '+d.getHours()+':'+('0'+d.getMinutes()).slice(-2); },
	yLabelFormat: function (y) { return (Math.pow(1.1,y)-1).toFixed(4); },
        goals: [0.0]
        }).on('click',click_callback).on('touchstart',click_callback);
      });
}

