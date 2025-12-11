<section class="synopsis">
    <h3><com:Literal id="Title" /></h3>
    <div class="code">
        <com:Repeater id="List" onItemDataBind="List_DataBind">
            <prop:headerTemplate>
                <ul>
            </prop:headerTemplate>
            <prop:itemTemplate>
                    <li>
                        <com:Text id="Modifiers" html.class="modifiers" /> <span class="const">const</span> 
                        <com:Text id="ConstantName" html.class="constant" /> = <com:Text id="ConstantValue" html.class="value" />
                        <com:Text id="InheritedFrom" visible="false" html.class="badge inherited" html.title="Inherited">
                            <com:HyperLink id="InheritedFromLink" />
                        </com:Text>
                        <com:.classes.Comment id="Comment" />
                    </li>
            </prop:itemTemplate>
            <prop:footerTemplate>
                </ul>
            </prop:footerTemplate>
        </com:Repeater>
    </div>
</section>
