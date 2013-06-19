function expand(URI,SUB,EXP,TMP){
  if(document.getElementById) {
    if(document.getElementById(SUB).style.display) {
      if(URI != 0) {
        document.getElementById(SUB).style.display = "block";
        document.getElementById(EXP).style.display = "none";
        document.getElementById(TMP).style.display = "none";
      } else {
        document.getElementById(SUB).style.display = "none";
        document.getElementById(EXP).style.display = "block";
        document.getElementById(TMP).style.display = "block";
      }
    } else {
      location.href = URI;
      return true;
    }
  } else {
    location.href = URI;
    return true;
  }
  
  
}
