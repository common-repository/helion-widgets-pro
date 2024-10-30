<?php

function helion_book_picker() {
	global $wpdb;
	
	if(!$_REQUEST['paged']) {
		$paged = 0;
	} else {
		if($REQUEST['kierunek'] == "prev") {
			$paged = intval($_REQUEST['paged']) - 2;
			if($paged < 0)
				$paged = 0;
		} else {
			$paged = intval($_REQUEST['paged']);
		}
	}

	if($_REQUEST['nazadanie'] == "1") {
		$nazadanie = "";
	} else {
		$nazadanie = " AND NOT nazadanie ";
	}
		
	$per_page = 10;
	$limit_bottom = $paged * $per_page;
	
	if($_REQUEST['fraza']) {
		$fraza = helion_parse_search($_REQUEST['fraza']);
		$rows_ksiazki = $wpdb->get_results($fraza, ARRAY_A);
	} else {
		$rows_ksiazki = $wpdb->get_results(
                        $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . ""
                                . "helion_books_helion "
                                . "WHERE cena AND marka = '1' " . $nazadanie . " "
                                . "LIMIT %d, %d", 
                                $limit_bottom, $per_page), ARRAY_A);
	}
	
	$wynikow = $wpdb->num_rows;

?>
<div class="helion-select-navi">
<?php if($paged > 0 && !$_REQUEST['fraza']) { ?>
	<a class="prev" rel="nofollow">&laquo;</a>
<?php } else if(!$_REQUEST['fraza']) { ?>
	<span class="empty">&laquo;</span>
<?php } ?>
	
	<select id="helion_search_rodzaj">
		<option value="tytul">Słowa w tytule</option>
		<option value="autor">Autor</option>
		<option value="ident">Ident</option>
	</select>
	<input type="text" id="helion_search" value="<?php echo strip_tags($_REQUEST['helion_search']); ?>" />
	<input type="button" id="helion_search_sb" value="Filtruj" />
	
<?php /* if(!$_REQUEST['fraza']) { ?>
	Strona <?php echo $wynikow / $per_page; ?> z <?php echo $wynikow / $per_page; ?>
<?php */ ?>	

<?php if(!$_REQUEST['fraza']) { ?>
	<a class="next" rel="nofollow">&raquo;</a>
<?php } ?>
</div>
<table class="widefat" id="tabela_wyboru">
	<thead>
		<tr>
			<th class="manage_column"><input type="button" name="wybierz_wszystkie" value="+"/></th>
			<th class="manage_column">Tytuł</th>
			<th class="manage_column">Ident</th>
			<th class="manage_column">Autor</th>
			<th class="manage_column">Marka</th>
			<th class="manage_column">Cena</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th class="manage_column"><input type="button" name="wybierz_wszystkie" value="+"/></th>
			<th class="manage_column">Tytuł</th>
			<th class="manage_column">Ident</th>
			<th class="manage_column">Autor</th>
			<th class="manage_column">Marka</th>
			<th class="manage_column">Cena</th>
		</tr>
	</tfoot>
	<tbody>
<?php if($rows_ksiazki) {
	
	foreach($rows_ksiazki as $row) { ?>
	<tr>
		<td>
			<?php if(stristr($row['ident'], "_") && $row['marka'] == "1") { ?>
			<input type="button" name="<?php echo "ebookpoint-" . $row['ident'] . "-" . $row['tytul']; ?>" value="+"/>
			<?php } else { ?>
			<input type="button" name="<?php echo helion_marka($row['marka']) . "-" . $row['ident'] . "-" . $row['tytul']; ?>" value="+"/>
			<?php } ?>
		</td>
		<td>
			<em><?php echo $row['tytul']; ?></em>
			<br/>
			Data wydania: <?php 
				$datawydania = explode("-", $row['datawydania']); 
				echo $datawydania[1] . "/" . $datawydania[0];
			?>
		</td>
		<td>
			<code><?php echo $row['ident']; ?></code>
		</td>
		<td>
			<?php echo $row['autor']; ?>
		</td>
		<td>
			<?php echo ucfirst(helion_marka($row['marka'])); ?>
		</td>
		<td>
			<?php echo $row['cena']; ?>
		</td>
	</tr>
<?php 
	} 
	}
?>
	</tbody>
</table>
<?php if(!$_REQUEST['fraza']) { ?>
<div class="helion-select-navi">
<?php if($paged > 0) { ?>
	<a class="prev" rel="nofollow">&laquo;</a>
<?php } else { ?>
	<span class="empty">&laquo;</span>
<?php }} ?>

<?php if(!$_REQUEST['fraza']) { ?>
	<a class="next" rel="nofollow">&raquo;</a>
<?php } ?>
</div>

<?php
	if($_REQUEST['ajax']) {
		die();
	}
}
?>
