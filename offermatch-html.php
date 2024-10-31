<?php
function offermatch_display_offer_form($offers)
{
    ?>    
	<div class="wrap">
	<h2>Create/Edit Your Offers</h2>
    <table class="widefat">
    <tr><th width="150">Title</th><th width="150">Offer Text/HTML</th>
    <th width="150">Tags</th><th width="50">Active</th><th width="50">Action</th></tr>
    </table>
    
    <form method="post">       
        <table class="widefat">
        <tr><td width="150"><input type="text" name="title"></td>
        <td width="150"><textarea rows="5" cols="20" name="offer"></textarea></td>
        <th width="150"><textarea rows="5" cols="20" name="tags"></textarea></th>
        <th width="50"><input type="checkbox" name="status" value="1" checked="true"> Active</th>
        <th width="50"><input type="submit" name="add_offer" value="Add Offer"></th></tr>
        </table>
        </form>
    
    <?php foreach($offers as $offer):?>
        <form method="post">
        <input type="hidden" name="id" value="<?=$offer->id?>">
        <input type="hidden" name="delete_offer" value="0">
        <table class="widefat">
        <tr><td width="150"><input type="text" name="title" value="<?=stripcslashes($offer->title)?>"></td>
        <td width="150"><textarea rows="5" cols="20" name="offer"><?=stripcslashes($offer->offer)?></textarea></td>
        <th width="150"><textarea rows="5" cols="20" name="tags"><?=stripcslashes($offer->tags)?></textarea></th>
        <th width="50"><input type="checkbox" name="status" value="1" <?php if($offer->status) echo "checked"?>> Active</th><th width="50"><input type="submit" name="save_offer" value="Save">
        <input type="button" value="Delete" onclick="confirmDelete(this.form);"></th></tr>
        </table>
        </form>
    <?php endforeach;?>    
    </div>
    <script type="text/javascript">
    function confirmDelete(frm)
    {
        if(confirm("Are you sure?"))
        {
            frm.delete_offer.value=1;
            frm.submit();
        }
    }
    </script>
    <?php    
}
?>