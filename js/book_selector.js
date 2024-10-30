function cap(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

var tablewait = '<div id="book_select"><div class="helion-select-navi"><span class="empty">«</span><select id="helion_search_rodzaj"><option value="tytul">Słowa w tytule</option><option value="autor">Autor</option><option value="ident">Ident</option></select><input type="text" value="" id="helion_search"><input type="button" value="Filtruj" id="helion_search_sb"><a class="next" rel="nofollow">»</a></div><table id="tabela_wyboru" class="widefat"><thead><tr><th class="manage_column"><input type="button" value="+" name="wybierz_wszystkie"></th><th class="manage_column">Tytuł</th><th class="manage_column">Ident</th><th class="manage_column">Autor</th><th class="manage_column">Marka</th><th class="manage_column">Cena</th></tr></thead><tfoot><tr><th class="manage_column"><input type="button" value="+" name="wybierz_wszystkie"></th><th class="manage_column">Tytuł</th><th class="manage_column">Ident</th><th class="manage_column">Autor</th><th class="manage_column">Marka</th><th class="manage_column">Cena</th></tr></tfoot><tbody><tr><td colspan="6">Pobieranie danych...</td></tr></tbody></table><div class="helion-select-navi"><span class="empty">«</span></div></div>';

jQuery(document).ready(function() {
	var i = 0;
	jQuery(".helion_page_helion_losowa_ksiazka table tbody input[type=button]").live('click', function() {
		if(jQuery(this).hasClass("cala_ksiegarnia")) {
			var ks = this.name.split("-");
			if(jQuery('input[value=' + ks[1] + ']').length == 0) {
				jQuery("#selected_books .items").append('<span><a class="bookdelete" id="book-check-num-' + i + '" rel="nofollow">X</a>&nbsp; Cała księgarnia ' + cap(ks[1]) + '<input type="hidden" name="ksiegarnie[]" value="' + ks[1] + '"/> </span> ');
				i++;
			}
		} else {
			var ks = this.name.split("-");
			if(jQuery('input[value=' + ks[0] + '_' + ks[1] + ']').length == 0) {
				jQuery("#selected_books .items").append('<span><a class="bookdelete" id="book-check-num-' + i + '" rel="nofollow">X</a>&nbsp;<code title="' + ks[2] + '">' + ks[1] + '</code><input type="hidden" name="books[]" value="' + ks[0] + '-' + ks[1] + '"/> </span> ');
				i++;
			}
		}
	});
	
	jQuery('input[name=wybierz_wszystkie]').live('click', function() {
		jQuery('#tabela_wyboru tbody td input').each(function() {
			this.click();
		});
	});
	
	jQuery(".bookdelete").live('click', function() {
		jQuery(this).parent().remove();
	});
	
	jQuery("input[name=helion_clear]").live('click', function() {
		jQuery('#selected_books .items').html('');
	});
	
	var page = 0;
	
	jQuery(".helion-select-navi .prev").live("click", function() {
		
		page--;
		var data = {
			action: 'helion_book_selector',
			kierunek: 'prev',
			paged: page,
			ajax: true,
		};
		
		jQuery("#book_select").html(tablewait);
		jQuery("#book_select").load(ajaxurl, data);
	});
	
	jQuery(".helion-select-navi .next").live("click", function() {
		
		page++;
		var data = {
			action: 'helion_book_selector',
			kierunek: 'next',
			paged: page,
			ajax: true,
		};
		
		jQuery("#book_select").html(tablewait);
		jQuery("#book_select").load(ajaxurl, data);
	});
	
	jQuery("#helion_search").live("keypress", function(e) {
		if(e.which == 13) {
			jQuery("#helion_search_sb").click();
		}
	})
	
	jQuery("#helion_search_sb").live("click", function() {
		var fraza = jQuery("#helion_search_rodzaj").val() + ":" + jQuery("#helion_search").val();
		
		var data = {
			action: 'helion_book_selector',
			ajax: true,
			fraza: fraza,
		};
		
		jQuery("#book_select").html(tablewait);
		jQuery("#book_select").load(ajaxurl, data);
	});
	
});