# Como Criar o Pull Request

## Opção 1: Via Interface Web do GitHub (MAIS FÁCIL) 🌟

### Passo a Passo:

1. **Acesse o repositório no GitHub:**
   ```
   https://github.com/brunadevfull/quadrodeoficiais
   ```

2. **Você verá um banner amarelo** dizendo:
   ```
   claude/debug-service-order-management-011CUQNdFe4FQBawQHaRQpD4 had recent pushes
   [Compare & pull request]
   ```

3. **Clique no botão verde "Compare & pull request"**

4. **Preencha o PR:**

   **Título:**
   ```
   Fix: Resolver erro no gerenciamento de oficiais de serviço (DATABASE_URL)
   ```

   **Base branch:** `master` (ou `main`)

   **Compare branch:** `claude/debug-service-order-management-011CUQNdFe4FQBawQHaRQpD4`

   **Descrição:** (cole o conteúdo do arquivo `PR_DESCRIPTION.md` abaixo)

5. **Clique em "Create pull request"**

---

## Opção 2: URL Direto

Acesse este link direto:
```
https://github.com/brunadevfull/quadrodeoficiais/compare/master...claude/debug-service-order-management-011CUQNdFe4FQBawQHaRQpD4?expand=1
```

---

## Opção 3: Via Git (Comando)

Se você tiver acesso ao `gh` CLI localmente:

```bash
cd /home/user/quadrodeoficiais

gh pr create \
  --title "Fix: Resolver erro no gerenciamento de oficiais de serviço (DATABASE_URL)" \
  --body-file PR_DESCRIPTION.md \
  --base master
```

---

## Após Criar o PR

### ✅ Checklist antes do Merge:

- [ ] Revisar os arquivos modificados
- [ ] Verificar se `.env` NÃO está incluído (deve estar protegido por `.gitignore`)
- [ ] Confirmar que `.env.example` está incluído
- [ ] Validar documentação

### ⚠️ Ações OBRIGATÓRIAS Após o Merge:

1. **Criar arquivo `.env` no servidor:**
   ```bash
   cd /caminho/do/projeto
   echo "DATABASE_URL=postgresql://postgres:suasenha123@localhost:5432/marinha_papem" > .env
   chmod 644 .env
   ```

2. **Reiniciar servidor web:**
   ```bash
   sudo systemctl restart apache2
   # ou
   sudo systemctl restart nginx
   sudo systemctl restart php7.4-fpm
   ```

3. **Testar a funcionalidade:**
   - Acessar "Gerenciar Oficiais de Serviço"
   - Verificar se dropdowns são populados
   - Testar salvar dados

---

## 📞 Suporte

Se tiver dúvidas sobre o PR ou a implementação, consulte:
- `SOLUCAO_IMPLEMENTADA.md` - Documentação completa
- `CONFIGURACAO_BANCO_DE_DADOS.md` - Guia técnico
- `DIAGNOSTICO_E_SOLUCAO.md` - Análise do problema
