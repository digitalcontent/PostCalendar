<fieldset>
    <legend>{gt text='PostCalendar hook settings' domain="module_postcalendar"}</legend>
    <div class="z-formrow">
        <label for="postcalendar_optoverride">{gt text="Allow item creator to opt in/out of event creation" domain="module_postcalendar"}</label>
        <input type="checkbox" value="1" id='postcalendar_optoverride' name='postcalendar[postcalendar_optoverride]' {if $postcalendar_optoverride} checked="checked"{/if}/>
    </div>
    <div class="z-formrow">
        <label for="postcalendar_cats">{gt text="Assign all events to category:" domain="module_postcalendar"}</label>
        {gt text="Allow creator to select" domain="module_postcalendar" assign="allText"}
        {nocache}
        <span>{foreach from=$postcalendar_catregistry key=property item=category}
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