<div class="attributes">
    <span class="attribute">#[</span>
    <com:Repeater id="List" onItemDataBind="List_DataBind">
        <prop:itemTemplate>
            <com:HyperLink id="Name" html.class="type" /><!--
         --><com:Repeater id="Args" onItemDataBind="Args_DataBind">
                <prop:headerTemplate>( </prop:headerTemplate>
                    <prop:itemTemplate trim="true">
                        <com:Text id="ArgName" html.class="name" visible="false" /><!--
                     --><com:Literal id="ArgColon" text=": " visible="false" /><!--
                     --><com:Text id="ArgValue" html.class="value" />
                    </prop:itemTemplate>
                    <prop:separatorTemplate>, </prop:separatorTemplate>
                    <prop:footerTemplate> )</prop:footerTemplate>
                </com:Repeater>
        </prop:itemTemplate>
        <prop:separatorTemplate>, </prop:separatorTemplate>
    </com:Repeater>
    <span class="attribute">]</span>
</div>