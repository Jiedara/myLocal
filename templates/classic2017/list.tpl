<form method="post" enctype="multipart/form-data" action="">
    <ul class="site-l">
    {foreach from=$datas item=data key=key}
        
        {if isset($data.folder)}
            {foreach from=$data.folder item=data key=key}
                {include file="site.tpl"}
            {/foreach}
        {else}
            {include file="site.tpl"}
        {/if}
    {/foreach}
    <li class="upload-img circle ">
        <input class='local-link' type="submit" name="submitImg" value="Uploader" />
    </li>
    </ul>
</form>