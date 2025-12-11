<ul class="tree">
    <com:Repeater id="Items" onItemDataBind="Items_DataBind">
        <prop:itemTemplate>
            <html:li id="ListItem">
                <com:HyperLink id="ItemName" /><com:Text id="ItemExt" html.class="ext" />
                <com:.classes.ItemsTree id="SubItems" />
            </html:li>
        </prop:itemTemplate>
    </com:Repeater>
</ul>
