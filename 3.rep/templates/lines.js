	    populate_graph_with_lines('/summary/1/{{data_size}}/{{values}}.json','{{values}}-line',function ( i, row ) {
	      if ( $('#news-table').length && $('#news-time').length ) {
	        populate_table_with_posts('/hnposts/'+row.offset+'/news.json','#news-table','#news-time');
	      }
	      if ( $('#newest-table').length && $('#newest-time').length ) {
	        populate_table_with_posts('/hnposts/'+row.offset+'/newest.json','#newest-table','#newest-time');  
	      }
	    });
