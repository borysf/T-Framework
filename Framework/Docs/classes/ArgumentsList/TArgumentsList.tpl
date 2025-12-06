<com:Repeater id="List" onItemDataBind="List_DataBind">
    <prop:itemTemplate>
        <span class="argument">
            <com:.classes.TypesList id="Type" />
            <com:Text id="Variadic" html.class="type variadic" visible="false">...</com:Text><!--
         --><% $args->data->isPassedByReference() ? '&': '' %>$<com:Text id="Name" html.class="name" />
            <com:Container id="Default" visible="false">
                = <com:Text id="Value" html.class="value" />
            </com:Container>
        </span>
    </prop:itemTemplate>
    <prop:separatorTemplate>, </prop:separatorTemplate>
    <prop:emptyTemplate>
        <span class="argument">
            <span class="type void">void</span>
        </span>
    </prop:emptyTemplate>
</com:Repeater>