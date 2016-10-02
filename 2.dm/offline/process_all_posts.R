## ================

library(rjson);
library(RCurl);
NEWEST_JSON <<- 'http://hnpickup.appspot.com/hnposts/##N##/newest.json';
NEWS_JSON <<- 'http://hnpickup.appspot.com/hnposts/##N##/news.json';

## ================

get_json_data <- function ( start = 1, limit = 1000 ) {

  last_etime     <- 1;
  n_slice        <- start;
  news_data      <- NULL;
  newest_data    <- NULL;
## ----------------
  while ( n_slice <= limit) {
    news_json    <- NEWS_JSON;
    news_json    <- sub('##N##',n_slice,news_json);
    news_json    <- fromJSON(getURL(news_json));
    if ( length(news_json) > 1 ) {
      last_etime   <- as.numeric(news_json[[1]]$etime);
      news_data    <- rbind(news_data,t(sapply(news_json,function(x){y<-as.character(x);names(y)<-names(x);return(y)})));
      newest_json  <- NEWEST_JSON;
      newest_json  <- sub('##N##',n_slice,newest_json);
      newest_json  <- fromJSON(getURL(newest_json));
      newest_data  <- rbind(newest_data,t(sapply(newest_json,function(x){y<-as.character(x);names(y)<-names(x);return(y)})));
      print(paste('Processed slice',n_slice,'of the data which resulted in',nrow(news_data),'and',nrow(newest_data),'rows of data at',last_etime));
      write.csv(news_data,file=paste('news_data',start,'.csv',sep=''),row.names=FALSE);
      write.csv(newest_data,file=paste('newest_data',start,'.csv',sep=''),row.names=FALSE);
    }
    Sys.sleep(1);
    n_slice      <- n_slice + 1;
  }
  news_data      <- unique(news_data);
  newest_data    <- unique(newest_data);
  write.csv(news_data,file=paste('news_data',start,'.csv',sep=''),row.names=FALSE);
  write.csv(newest_data,file=paste('newest_data',start,'.csv',sep=''),row.names=FALSE);
## ----------------
  return(list(newest_data=newest_data,news_data=news_data));

}

## ================

process_csv <- function ( file_name = 'newest_data1.csv', main_var = 'points' ) {

## ----------------
  dat <- read.csv(file_name);
  dat <- unique(dat);
  mat <- aggregate(dat[[main_var]],list(dat$etime),function(x){x<-as.numeric(x);x[x==0]<-round(mean(x));return(sort(x));});
  com <- aggregate(as.numeric(dat$compare)*as.numeric(dat$points),list(dat$etime),function(x){return(sum(as.numeric(x)))})[,2];
  MAT <- mat[,2];
  tim <- mat[,1];
  res <- cbind(MAT,com);
  rownames(res) <- tim;
## ----------------
  return(res);

}

## ================

time_shift_and_lag <- function ( mat, target = nrow(mat), shift = 1, lag = 2 ) {

## ----------------


## ----------------

}




