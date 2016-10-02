
## =====
## -- https://github.com/mcordingley/Regression

library(rjson);

## =====

convert_json_all_summary <- function ( file_name, smooth, time_shift ) {

  dat <- fromJSON(file=file_name);
## -----
  mat <- t(sapply(dat,function(x){y<-as.numeric(x);names(y)<-names(x);return(y)}));
  mat <- apply(mat,2,function(x){sapply(seq(x)[-(1:(smooth-1))],function(i){mean(x[(i-smooth+1):(i)])})});
  mat <- data.frame(mat);
  mat_prev <- mat[-((nrow(mat)-time_shift+1):(nrow(mat))),];
  mat_next <- mat[-(1:time_shift),];
  colnames(mat_prev) <- paste('prev',colnames(mat_prev),sep='.')
  colnames(mat_next) <- paste('next',colnames(mat_next),sep='.')
  mat <- cbind(mat_prev,mat_next);
## -----
  return(mat);

}

## =====


#setRefClass("MyClass",
#    fields=list(
#      name="character",
#      ref="ANY"
#      )
#    );
