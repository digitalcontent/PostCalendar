{admincategorymenu}
<div class="z-adminbox">
	<h1>{gt text=$ActiveModule}</h1>
    {modulelinks modname=$ActiveModule type='admin'}
</div>

<div class="z-admincontainer">
<div class="z-adminpageicon">{img modname='PostCalendar' src='admin.png'}</div>
<h2>{gt text="PostCalendar settings for %s" tag1=$ActiveModule}</h2>
<form class="z-form" action="{modurl modname=$ActiveModule type="admin" func="postcalendarhookconfigprocess"}" method="post" enctype="application/x-www-form-urlencoded">
    <div>
	<input type="hidden" name="postcalendar[postcalendar_csrftoken]" value="{insert name="csrftoken"}" />
	<input type="hidden" name="ActiveModule" value="{$ActiveModule}" />

    <fieldset>
        <legend>{gt text='PostCalendar hook option settings' domain="module_postcalendar"}</legend>
        <p class="z-informationmsg">{gt text='These settings will apply only to the %s module. They will only function if %s is hooked to PostCalendar.' tag1=$ActiveModule tag2=$ActiveModule}</p>
        <div class="z-formrow">
            <label for="postcalendar_optoverride">{gt text="Allow item creator to opt in/out of event creation" domain="module_postcalendar"}</label>
            <input type="checkbox" value="1" id='postcalendar_optoverride' name='postcalendar[postcalendar_optoverride]' {if $postcalendar_optoverride} checked="checked"{/if}/>
        </div>
        <div class="z-formrow">
            <label for="postcalendar_cats">{gt text="Assign all events to category:" domain="module_postcalendar"}</label>
            {gt text="Allow creator to select" domain="module_postcalendar" assign="allText"}
            {nocache}
            <span>{foreach from=$postcalendar_catregistry key='property' item='category'}
                {array_field_isset assign="selectedValue" array=$postcalendar_admincatselected field=$property returnValue=1}
                {selector_category
                    editLink=true
                    category=$category
                    name="postcalendar[postcalendar_admincatselected][$property]"
                    field="id"
                    selectedValue=$selectedValue
                    all=1
                    allText=$allText
                    allValue=0}
                {/foreach}</span>
            {/nocache}
        </div>
    </fieldset>
    <div class="z-buttons z-formbuttons">
        {button src="button_ok.png" set="icons/extrasmall" class='z-btgreen' __alt="Save" __title="Save" __text="Save"}
        <a class='z-btred' href="{modurl modname=$ActiveModule type="admin" func='listevents'}" title="{gt text="Cancel"}">{img modname='core' src="button_cancel.png" set="icons/extrasmall" __alt="Cancel" __title="Cancel"} {gt text="Cancel"}</a>
    </div>
    </div>
</form>
</div>